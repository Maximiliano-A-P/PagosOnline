<?php

namespace App\Services\Arca;

use App\Models\ArcaConfig;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ArcaApiService
{
    private const CONSUMIDOR_FINAL_IVA = 5;
    private const CONCEPTO_SERVICIOS = 2;

    private const ALICUOTAS_AFIP = [
        0 => 3,
        10.5 => 4,
        21 => 5,
        27 => 6,
        5 => 8,
        2.5 => 9,
    ];

    public function __construct(
        private readonly WsaaClient $wsaaClient,
        private readonly ArcaConfig $config,
    ) {}

    public function emitir(Invoice $invoice): void
    {
        /*
         * Una factura ya autorizada no vuelve a emitirse.
         */
        if (
            $invoice->arca_status === 'aprobado'
            || !empty($invoice->arca_cae)
        ) {
            return;
        }

        /*
         * Registramos cuándo se intentó emitir.
         */
        $invoice->update([
            'arca_last_attempt_at' => now(),
        ]);

        $credenciales = $this->wsaaClient->obtenerCredenciales();

        $condicionCliente = $invoice->client_iva_condition
            ?? self::CONSUMIDOR_FINAL_IVA;

        $tipoComprobante = ComprobanteResolver::determinarTipoComprobante(
            $this->config->condicion_iva,
            $invoice->client_iva_condition
        );

        /*
         * Factura A requiere CUIT.
         *
         * Esto es un error de datos y NO debe entrar
         * en el reintento automático.
         */
        if (
            ComprobanteResolver::requiereCuit($tipoComprobante)
            && empty($invoice->client_cuit)
        ) {
            $this->marcarError(
                $invoice,
                'El cliente debe tener CUIT cargada para facturar como Responsable Inscripto (Factura A).'
            );

            return;
        }

        [$docTipo, $docNro] = array_values(
            ComprobanteResolver::resolverDocumento(
                $tipoComprobante,
                (string) $invoice->client_document,
                $invoice->client_cuit
            )
        );

        $total = (float) $invoice->amount_paid;

        $porcentajeIva = (float) (
            $invoice->tax_percentage ?? 0
        );

        $neto = round(
            $total / (1 + $porcentajeIva / 100),
            2
        );

        $iva = round(
            $total - $neto,
            2
        );

        $payload = [
            'token' =>
                $credenciales['token'],

            'sign' =>
                $credenciales['sign'],

            'cuit' =>
                $this->config->cuit,

            'puntoVenta' =>
                $this->config->punto_venta,

            'tipoComprobante' =>
                $tipoComprobante,

            'concepto' =>
                self::CONCEPTO_SERVICIOS,

            'docTipo' =>
                $docTipo,

            'docNro' =>
                $docNro,

            'condicionIvaReceptorId' =>
                $condicionCliente,

            'fecha' =>
                $invoice->issued_at->format('Ymd'),

            'importeNeto' =>
                $neto,

            'importeIva' =>
                $iva,

            'importeTotal' =>
                $total,

            'fchServDesde' =>
                $invoice->service_period_start?->format('Ymd'),

            'fchServHasta' =>
                $invoice->service_period_end?->format('Ymd'),

            'fchVtoPago' =>
                $fechaVencimiento->format('Ymd'),
        ];

        if (
            ComprobanteResolver::discriminaIva($tipoComprobante)
            && $iva > 0
        ) {
            $payload['alicuotasIva'] = [[
                'id' =>
                    $this->alicuotaIdParaPorcentaje(
                        $porcentajeIva
                    ),

                'baseImp' =>
                    $neto,

                'importe' =>
                    $iva,
            ]];
        }

        try {
            $respuesta = Http::withHeaders([
                'X-Internal-Api-Key' =>
                    config('arca.api_key'),
            ])
                ->timeout(30)
                ->post(
                    config('arca.api_url') . '/wsfe/solicitar-cae',
                    $payload
                );

        } catch (ConnectionException $e) {

            $this->marcarReintento(
                $invoice,
                'Error de conexión con la API ARCA: '
                . $e->getMessage()
            );

            return;
        }

        /*
         * Errores HTTP transitorios.
         */
        if (
            $respuesta->status() === 408
            || $respuesta->status() === 429
            || $respuesta->serverError()
        ) {
            $this->marcarReintento(
                $invoice,
                sprintf(
                    'HTTP %s. Respuesta API ARCA: %s',
                    $respuesta->status(),
                    trim($respuesta->body())
                )
            );

            return;
        }

        $this->procesarRespuesta(
            $invoice,
            $respuesta,
            $tipoComprobante
        );
    }

    private function alicuotaIdParaPorcentaje(
        float $porcentaje
    ): int {
        return self::ALICUOTAS_AFIP[$porcentaje]
            ?? self::ALICUOTAS_AFIP[21];
    }

    private function procesarRespuesta(
        Invoice $invoice,
        $respuesta,
        int $tipoComprobante
    ): void {
        $datos = $respuesta->json();

        if (
            $respuesta->successful()
            && ($datos['exito'] ?? false)
        ) {
            $invoice->update([
                'arca_status' =>
                    'aprobado',

                'arca_cae' =>
                    $datos['cae'],

                'arca_cae_expires_at' =>
                    $this->convertirFechaCae(
                        $datos['caeVencimiento'] ?? null
                    ),

                'arca_invoice_type' =>
                    $tipoComprobante,

                'arca_point_of_sale' =>
                    $this->config->punto_venta,

                'arca_invoice_number' =>
                    $datos['numeroComprobante'],

                'arca_retry_attempts' =>
                    0,

                'arca_retry_at' =>
                    null,
            ]);

            return;
        }

        /*
         * Errores funcionales de ARCA.
         *
         * No se reintentan automáticamente.
         */
        $mensaje = collect(
            $datos['errores'] ?? []
        )
            ->pluck('mensaje')
            ->implode(' | ');

        if (!$mensaje && isset($datos['error'])) {
            $mensaje = (string) $datos['error'];
        }

        if (!$mensaje) {
            $mensaje = sprintf(
                'HTTP %s. Respuesta API ARCA: %s',
                $respuesta->status(),
                $respuesta->body()
            );
        }

        $this->marcarError(
            $invoice,
            $mensaje
        );
    }

    private function convertirFechaCae(
        ?string $fecha
    ): ?Carbon {
        if (!$fecha) {
            return null;
        }

        return Carbon::createFromFormat(
            'Ymd',
            $fecha
        )->startOfDay();
    }

    private function marcarReintento(
        Invoice $invoice,
        string $mensaje
    ): void {
        $intentos =
            ((int) $invoice->arca_retry_attempts) + 1;

        $maxIntentos =
            (int) config(
                'arca.retry.max_attempts',
                8
            );

        /*
         * Llegamos al máximo.
         */
        if ($intentos > $maxIntentos) {
            $invoice->update([
                'arca_status' =>
                    'error: '
                    . $mensaje
                    . ' Se agotaron los reintentos automáticos.',

                'arca_retry_attempts' =>
                    $intentos,

                'arca_retry_at' =>
                    null,
            ]);

            return;
        }

        $base =
            max(
                1,
                (int) config(
                    'arca.retry.delay_minutes',
                    10
                )
            );

        $maxBackoff =
            max(
                $base,
                (int) config(
                    'arca.retry.max_backoff_minutes',
                    120
                )
            );

        $delay =
            min(
                $base * (2 ** ($intentos - 1)),
                $maxBackoff
            );

        $invoice->update([
            'arca_status' =>
                'error: '
                . $mensaje
                . ' Reintento automático programado en '
                . $delay
                . ' minutos.',

            'arca_retry_attempts' =>
                $intentos,

            'arca_retry_at' =>
                now()->addMinutes($delay),
        ]);
    }

    private function marcarError(
        Invoice $invoice,
        string $mensaje
    ): void {
        $invoice->update([
            'arca_status' =>
                'error: ' . $mensaje,

            /*
             * Error permanente: no se mete en el
             * reintento automático.
             */
            'arca_retry_at' =>
                null,
        ]);
    }
}