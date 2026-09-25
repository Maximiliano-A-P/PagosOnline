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

    /**
     * Emite una factura ante ARCA.
     *
     * $automatico = true
     *     cuando viene del proceso automático.
     *
     * $automatico = false
     *     cuando viene del webhook o del botón manual.
     */
    public function emitir(
        Invoice $invoice,
        bool $automatico = false
    ): void {
        /*
         * Una factura ya autorizada nunca vuelve a emitirse.
         */
        if (
            $invoice->arca_status === 'aprobado'
            || !empty($invoice->arca_cae)
        ) {
            return;
        }

        /*
         * Solo el proceso automático respeta
         * el horario programado.
         *
         * El botón manual ignora arca_retry_at.
         */
        if (
            $automatico
            && $invoice->arca_retry_at
            && $invoice->arca_retry_at->isFuture()
        ) {
            return;
        }

        /*
         * Registramos el último intento.
         */
        $invoice->update([
            'arca_last_attempt_at' => now(),
        ]);

        /*
         * ==========================================================
         * WSAA
         * ==========================================================
         */

        try {
            $credenciales =
                $this->wsaaClient->obtenerCredenciales();

        } catch (\Throwable $e) {

            $mensaje =
                'Error al autenticar contra WSAA: '
                . $e->getMessage();

            /*
             * Si parece un error transitorio,
             * programamos reintento.
             */
            if (
                $this->esErrorTransitorio(
                    $e->getMessage()
                )
            ) {
                $this->registrarFalloTransitorio(
                    $invoice,
                    $mensaje,
                    $automatico
                );

                return;
            }

            /*
             * Error permanente de autenticación/configuración.
             */
            $this->marcarError(
                $invoice,
                $mensaje
            );

            return;
        }

        /*
         * ==========================================================
         * TIPO DE COMPROBANTE
         * ==========================================================
         */

        $condicionCliente =
            $invoice->client_iva_condition
            ?? self::CONSUMIDOR_FINAL_IVA;

        $tipoComprobante =
            ComprobanteResolver::determinarTipoComprobante(
                $this->config->condicion_iva,
                $invoice->client_iva_condition
            );

        /*
         * Factura A requiere CUIT.
         *
         * Error permanente de datos.
         */
        if (
            ComprobanteResolver::requiereCuit(
                $tipoComprobante
            )
            && empty($invoice->client_cuit)
        ) {
            $this->marcarError(
                $invoice,
                'El cliente debe tener CUIT cargada para facturar como Responsable Inscripto (Factura A).'
            );

            return;
        }

        /*
         * ==========================================================
         * DOCUMENTO DEL RECEPTOR
         * ==========================================================
         */

        [$docTipo, $docNro] =
            array_values(
                ComprobanteResolver::resolverDocumento(
                    $tipoComprobante,
                    (string) $invoice->client_document,
                    $invoice->client_cuit
                )
            );

        /*
         * ==========================================================
         * FECHAS
         * ==========================================================
         */

        $fechaComprobante =
            $invoice->issued_at
                ->copy()
                ->startOfDay();

        $fechaVencimiento =
            $invoice->due_date
                ->copy()
                ->startOfDay();

        /*
         * ARCA no permite vencimiento anterior
         * a la fecha del comprobante.
         */
        if (
            $fechaVencimiento->lt(
                $fechaComprobante
            )
        ) {
            $fechaVencimiento =
                $fechaComprobante->copy();
        }

        /*
         * ==========================================================
         * IMPORTES
         * ==========================================================
         */

        $total =
            (float) $invoice->amount_paid;

        $porcentajeIva =
            (float) (
                $invoice->tax_percentage ?? 0
            );

        $neto =
            round(
                $total
                / (1 + $porcentajeIva / 100),
                2
            );

        $iva =
            round(
                $total - $neto,
                2
            );

        /*
         * ==========================================================
         * PAYLOAD
         * ==========================================================
         */

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
                $fechaComprobante->format('Ymd'),

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

        /*
         * ==========================================================
         * IVA DISCRIMINADO
         * ==========================================================
         */

        if (
            ComprobanteResolver::discriminaIva(
                $tipoComprobante
            )
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

        /*
         * ==========================================================
         * LLAMADA A API ARCA
         * ==========================================================
         */

        try {
            $respuesta =
                Http::withHeaders([
                    'X-Internal-Api-Key' =>
                        config('arca.api_key'),
                ])
                    ->timeout(30)
                    ->post(
                        config('arca.api_url')
                        . '/wsfe/solicitar-cae',
                        $payload
                    );

        } catch (ConnectionException $e) {

            $this->registrarFalloTransitorio(
                $invoice,
                'Error de conexión con la API ARCA: '
                . $e->getMessage(),
                $automatico
            );

            return;
        }

        /*
         * ==========================================================
         * ERRORES HTTP TRANSITORIOS
         * ==========================================================
         *
         * 408 = timeout
         * 429 = demasiadas solicitudes
         * 5xx = error de servidor
         */

        if (
            $respuesta->status() === 408
            || $respuesta->status() === 429
            || $respuesta->serverError()
        ) {
            $this->registrarFalloTransitorio(
                $invoice,
                sprintf(
                    'HTTP %s. Respuesta API ARCA: %s',
                    $respuesta->status(),
                    trim($respuesta->body())
                ),
                $automatico
            );

            return;
        }

        /*
         * ==========================================================
         * PROCESAR RESPUESTA
         * ==========================================================
         */

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
        $datos =
            $respuesta->json();

        /*
         * ==========================================================
         * APROBADO
         * ==========================================================
         */

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

                /*
                 * Ya no necesita reintentos.
                 */
                'arca_retry_attempts' =>
                    0,

                'arca_retry_at' =>
                    null,
            ]);

            return;
        }

        /*
         * ==========================================================
         * ERROR FUNCIONAL
         * ==========================================================
         */

        $mensaje =
            collect(
                $datos['errores'] ?? []
            )
                ->pluck('mensaje')
                ->implode(' | ');

        if (
            !$mensaje
            && isset($datos['error'])
        ) {
            $mensaje =
                (string) $datos['error'];
        }

        if (!$mensaje) {
            $mensaje =
                sprintf(
                    'HTTP %s. Respuesta API ARCA: %s',
                    $respuesta->status(),
                    $respuesta->body()
                );
        }

        /*
         * Los errores funcionales de ARCA no se
         * reintentan automáticamente.
         */
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

    /**
     * Registra un error transitorio.
     *
     * En automático:
     *   aumenta el contador.
     *
     * En manual:
     *   NO aumenta el contador.
     *
     * En ambos casos queda programado un próximo
     * intento automático.
     */
    private function registrarFalloTransitorio(
        Invoice $invoice,
        string $mensaje,
        bool $automatico
    ): void {
        $intentosActuales =
            (int) (
                $invoice->arca_retry_attempts ?? 0
            );

        $maxIntentos =
            max(
                1,
                (int) config(
                    'arca.retry.max_attempts',
                    8
                )
            );

        /*
         * Si fue un intento automático, este fallo
         * consume un intento.
         */
        if ($automatico) {

            $proximoIntento =
                $intentosActuales + 1;

            if (
                $proximoIntento > $maxIntentos
            ) {
                $invoice->update([
                    'arca_status' =>
                        'error: '
                        . $mensaje
                        . ' Se agotaron los reintentos automáticos.',

                    'arca_retry_attempts' =>
                        $proximoIntento,

                    'arca_retry_at' =>
                        null,
                ]);

                return;
            }

            $invoice->update([
                'arca_retry_attempts' =>
                    $proximoIntento,
            ]);

        } else {

            /*
             * El intento manual NO consume un intento
             * automático.
             */
            $proximoIntento =
                max(
                    1,
                    $intentosActuales + 1
                );
        }

        /*
         * ==========================================================
         * BACKOFF
         * ==========================================================
         */

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
                $base
                * (2 ** ($proximoIntento - 1)),
                $maxBackoff
            );

        $invoice->update([
            'arca_status' =>
                'error: '
                . $mensaje
                . ' Reintento automático programado en '
                . $delay
                . ' minutos.',

            'arca_retry_at' =>
                now()->addMinutes($delay),
        ]);
    }

    private function esErrorTransitorio(
        string $mensaje
    ): bool {
        $mensaje =
            strtolower($mensaje);

        return str_contains(
            $mensaje,
            '429'
        )
        || str_contains(
            $mensaje,
            'too many requests'
        )
        || str_contains(
            $mensaje,
            'timeout'
        )
        || str_contains(
            $mensaje,
            'timed out'
        )
        || str_contains(
            $mensaje,
            'connection'
        )
        || str_contains(
            $mensaje,
            'temporarily unavailable'
        )
        || str_contains(
            $mensaje,
            '502'
        )
        || str_contains(
            $mensaje,
            '503'
        )
        || str_contains(
            $mensaje,
            '504'
        );
    }

    private function marcarError(
        Invoice $invoice,
        string $mensaje
    ): void {
        $invoice->update([
            'arca_status' =>
                'error: ' . $mensaje,

            /*
             * Error funcional/permanente.
             */
            'arca_retry_at' =>
                null,
        ]);
    }
}