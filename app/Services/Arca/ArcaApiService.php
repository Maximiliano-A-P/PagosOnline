<?php

namespace App\Services\Arca;

use App\Models\ArcaConfig;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ArcaApiService
{
    private const CONSUMIDOR_FINAL_IVA = 5;
    private const CONSUMIDOR_FINAL_DOC_TIPO = 99;

    // Código AFIP fijo para "Servicios" — ver nota más abajo
    // sobre por qué está hardcodeado.
    private const CONCEPTO_SERVICIOS = 2;

    public function __construct(
        private readonly WsaaClient $wsaaClient,
        private readonly ArcaConfig $config,
    ) {}

    /**
     * Emite la factura ante ARCA a través de la API Node, y
     * guarda el resultado (CAE o rechazo) directamente en la
     * Invoice.
     */
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
            'importeTotal' => (float) $invoice->price,
            'importeNeto' => (float) $invoice->price,

            'fchServDesde' => $invoice->service_period_start?->format('Ymd'),
            'fchServHasta' => $invoice->service_period_end?->format('Ymd'),
            'fchVtoPago' => $invoice->due_date->format('Ymd'),
        ];

        $respuesta = Http::withHeaders([
            'X-Internal-Api-Key' => config('arca.api_key'),
        ])
            ->timeout(30)
            ->post(
                config('arca.api_url') . '/wsfe/solicitar-cae',
                $payload
            );

        $this->procesarRespuesta($invoice, $respuesta);
    }

    /**
     * Interpreta la respuesta de la API Node y actualiza la
     * Invoice según corresponda.
     */
    private function procesarRespuesta(Invoice $invoice, $respuesta): void
    {
        $datos = $respuesta->json();

        if ($respuesta->successful() && ($datos['exito'] ?? false)) {
            $invoice->update([
                'arca_status' => 'aprobado',
                'arca_cae' => $datos['cae'],
                'arca_cae_expires_at' => $datos['caeVencimiento'],
                'arca_invoice_type' => $invoice->client_iva_condition,
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