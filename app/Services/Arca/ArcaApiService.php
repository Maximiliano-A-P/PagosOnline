<?php

namespace App\Services\Arca;

use App\Models\ArcaConfig;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;

class ArcaApiService
{
    private const CONSUMIDOR_FINAL_IVA = 5;
    private const CONCEPTO_SERVICIOS = 2;

    // Mapeo de porcentaje de IVA -> código de alícuota AFIP,
    // usado solo cuando corresponde discriminar IVA (Factura A/M).
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
        $credenciales = $this->wsaaClient->obtenerCredenciales();

        $condicionCliente = $invoice->client_iva_condition
            ?? self::CONSUMIDOR_FINAL_IVA;

        $tipoComprobante = ComprobanteResolver::determinarTipoComprobante(
            $this->config->condicion_iva,
            $invoice->client_iva_condition
        );

        [$docTipo, $docNro] = array_values(
            ComprobanteResolver::resolverDocumento(
                $invoice->client_document_type,
                (string) $invoice->client_document
            )
        );

        if (
            ComprobanteResolver::requiereCuit($tipoComprobante)
            && $docTipo !== 80
        ) {
            $this->marcarError(
                $invoice,
                'El cliente debe tener CUIT cargado para facturar como Responsable Inscripto (Factura A).'
            );
            return;
        }

        // --- Cálculo del monto a facturar (con o sin mora) ---

        $neto = $this->determinarBaseNeto($invoice);
        $porcentajeIva = (float) ($invoice->tax_percentage ?? 0);
        $iva = round($neto * $porcentajeIva / 100, 2);
        $total = $neto + $iva;

        $payload = [
            'token' => $credenciales['token'],
            'sign' => $credenciales['sign'],
            'cuit' => $this->config->cuit,

            'puntoVenta' => $this->config->punto_venta,
            'tipoComprobante' => $tipoComprobante,
            'concepto' => self::CONCEPTO_SERVICIOS,

            'docTipo' => $docTipo,
            'docNro' => $docNro,
            'condicionIvaReceptorId' => $condicionCliente,

            'fecha' => $invoice->issued_at->format('Ymd'),
            'importeNeto' => $neto,
            'importeIva' => $iva,
            'importeTotal' => $total,

            'fchServDesde' => $invoice->service_period_start?->format('Ymd'),
            'fchServHasta' => $invoice->service_period_end?->format('Ymd'),
            'fchVtoPago' => $invoice->due_date->format('Ymd'),
        ];

        // Factura A/M: hay que discriminar el IVA en detalle.
        if (ComprobanteResolver::discriminaIva($tipoComprobante) && $iva > 0) {
            $payload['alicuotasIva'] = [[
                'id' => $this->alicuotaIdParaPorcentaje($porcentajeIva),
                'baseImp' => $neto,
                'importe' => $iva,
            ]];
        }

        $respuesta = Http::withHeaders([
            'X-Internal-Api-Key' => config('arca.api_key'),
        ])
            ->timeout(30)
            ->post(
                config('arca.api_url') . '/wsfe/solicitar-cae',
                $payload
            );

        $this->procesarRespuesta($invoice, $respuesta, $tipoComprobante);
    }

    /**
     * Decide si corresponde facturar el precio normal o el
     * precio con recargo por mora — ARCA no tiene ninguna
     * noción de esto, así que la decisión es 100% nuestra
     * ANTES de armar el payload.
     */
    private function determinarBaseNeto(Invoice $invoice): float
    {
        $pagadoConMora = $invoice->paid_at
            && $invoice->paid_at->greaterThan($invoice->due_date);

        return (float) ($pagadoConMora
            ? $invoice->overdue_price
            : $invoice->price);
    }

    private function alicuotaIdParaPorcentaje(float $porcentaje): int
    {
        return self::ALICUOTAS_AFIP[$porcentaje] ?? self::ALICUOTAS_AFIP[21];
    }

    private function procesarRespuesta(Invoice $invoice, $respuesta, int $tipoComprobante): void
    {
        $datos = $respuesta->json();

        if ($respuesta->successful() && ($datos['exito'] ?? false)) {
            $invoice->update([
                'arca_status' => 'aprobado',
                'arca_cae' => $datos['cae'],
                'arca_cae_expires_at' => $datos['caeVencimiento'],
                'arca_invoice_type' => $tipoComprobante,
                'arca_point_of_sale' => $this->config->punto_venta,
                'arca_invoice_number' => $datos['numeroComprobante'],
            ]);
            return;
        }

        $mensaje = collect($datos['errores'] ?? [])
            ->pluck('mensaje')
            ->implode(' | ');

        $this->marcarError(
            $invoice,
            $mensaje ?: 'Error desconocido al comunicarse con la API ARCA.'
        );
    }

    private function marcarError(Invoice $invoice, string $mensaje): void
    {
        $invoice->update([
            'arca_status' => 'error: ' . $mensaje,
        ]);
    }
}