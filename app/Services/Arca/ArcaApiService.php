<?php

namespace App\Services\Arca;

use App\Models\ArcaConfig;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArcaApiService
{
    private const CONSUMIDOR_FINAL_IVA = 5;
    private const CLIENTE_EXTERIOR = 9;
    private const CONCEPTO_SERVICIOS = 2;

    /*
     * Unidades de medida de WSFEX.
     * El código 7 se usa como unidad en el ejemplo del manual.
     */
    private const UNIDAD_MEDIDA_UNIDAD = 7;

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
     * La emisión solo se permite cuando la factura está pagada.
     * $automatico = true cuando viene del proceso automático.
     * $automatico = false cuando viene del webhook o del botón manual.
     */
    public function emitir(
        Invoice $invoice,
        bool $automatico = false
    ): void {
        /*
         * No registrar intentos ni contactar a ARCA antes de confirmar
         * el pago. Esta validación también protege las llamadas directas
         * desde controladores, webhooks y procesos automáticos.
         */
        if ($invoice->payment_status !== 'paid') {
            return;
        }

        if (
            $invoice->arca_status === 'aprobado'
            || ! empty($invoice->arca_cae)
        ) {
            return;
        }

        if (
            $automatico
            && $invoice->arca_retry_at
            && $invoice->arca_retry_at->isFuture()
        ) {
            return;
        }

        $momentoIntento = now();

        $invoice->update([
            'arca_last_attempt_at' => $momentoIntento,
        ]);

        if (! config('arca.cuit')) {
            $this->marcarError(
                $invoice,
                'Falta configurar ARCA_CUIT en el .env.'
            );

            return;
        }

        $tipoComprobante = ComprobanteResolver::determinarTipoComprobante(
            (int) $this->config->condicion_iva,
            $invoice->client_iva_condition
        );

        /*
         * Los comprobantes E se envían mediante WSFEXv1.
         * El resto continúa utilizando WSFEv1.
         */
        if (ComprobanteResolver::esExportacion($tipoComprobante)) {
            $this->emitirExportacion(
                $invoice,
                $automatico,
                $momentoIntento,
                $tipoComprobante
            );

            return;
        }

        $this->emitirComprobanteNacional(
            $invoice,
            $automatico,
            $momentoIntento,
            $tipoComprobante
        );
    }

    /**
     * Emisión de comprobantes A, B y C por WSFEv1.
     */
    private function emitirComprobanteNacional(
        Invoice $invoice,
        bool $automatico,
        Carbon $momentoIntento,
        int $tipoComprobante
    ): void {
        try {
            $credenciales = $this->wsaaClient->obtenerCredenciales();
        } catch (\Throwable $e) {
            $this->procesarErrorAutenticacion(
                $invoice,
                $automatico,
                $e
            );

            return;
        }

        $condicionCliente = $invoice->client_iva_condition
            ?? self::CONSUMIDOR_FINAL_IVA;

        if (
            ComprobanteResolver::requiereCuit($tipoComprobante)
            && empty($invoice->client_cuit)
        ) {
            $this->marcarError(
                $invoice,
                'El cliente debe tener CUIT cargado para emitir este comprobante A.'
            );

            return;
        }

        $documento = ComprobanteResolver::resolverDocumento(
            $tipoComprobante,
            (string) $invoice->client_document,
            $invoice->client_cuit
        );

        $fechaComprobante = $this->fechaComprobante($momentoIntento);

        $fechaVencimiento = $invoice->due_date->copy()->startOfDay();

        if ($fechaVencimiento->lt($fechaComprobante)) {
            $fechaVencimiento = $fechaComprobante->copy();
        }

        $total = round((float) $invoice->amount_paid, 2);
        $porcentajeIva = (float) ($invoice->tax_percentage ?? 0);

        if (in_array($tipoComprobante, [11, 12, 13], true)) {
            $neto = $total;
            $iva = 0.00;
        } else {
            $neto = round(
                $total / (1 + $porcentajeIva / 100),
                2
            );

            $iva = round($total - $neto, 2);
        }

        $payload = [
            'token' => $credenciales['token'],
            'sign' => $credenciales['sign'],
            'cuit' => preg_replace(
                '/\D/',
                '',
                (string) config('arca.cuit')
            ),
            'puntoVenta' => $this->config->punto_venta,
            'tipoComprobante' => $tipoComprobante,
            'concepto' => self::CONCEPTO_SERVICIOS,
            'docTipo' => $documento['docTipo'],
            'docNro' => $documento['docNro'],
            'condicionIvaReceptorId' => $condicionCliente,
            'fecha' => $fechaComprobante->format('Ymd'),
            'importeNeto' => $neto,
            'importeIva' => $iva,
            'importeTotal' => $total,
            'fchServDesde' => (
                $invoice->service_period_start
                ?? $invoice->issued_at->copy()->startOfMonth()
            )->format('Ymd'),
            'fchServHasta' => (
                $invoice->service_period_end
                ?? $invoice->issued_at->copy()->endOfMonth()
            )->format('Ymd'),
            'fchVtoPago' => $fechaVencimiento->format('Ymd'),
            'invoiceId' => $invoice->id,
            'reintentoAutomatico' => $automatico,
        ];

        if (
            ComprobanteResolver::discriminaIva($tipoComprobante)
            && $iva > 0
        ) {
            $payload['alicuotasIva'] = [[
                'id' => $this->alicuotaIdParaPorcentaje(
                    $porcentajeIva
                ),
                'baseImp' => $neto,
                'importe' => $iva,
            ]];
        }

        $respuesta = $this->enviarSolicitud(
            $invoice,
            $automatico,
            '/wsfe/solicitar-cae',
            $payload
        );

        if ($respuesta !== null) {
            $this->procesarRespuesta(
                $invoice,
                $respuesta,
                $tipoComprobante
            );
        }
    }

    /**
     * Emisión de facturas de exportación mediante WSFEXv1.
     *
     * Se requiere que la factura tenga los datos del comprador
     * del exterior guardados como snapshot.
     */
    private function emitirExportacion(
        Invoice $invoice,
        bool $automatico,
        Carbon $momentoIntento,
        int $tipoComprobante
    ): void {
        $domicilioCliente = trim(
            (string) ($invoice->client_address ?? '')
        );

        $paisDestino = $invoice->client_arca_destination_country
            ?? null;

        $cuitPaisCliente = $invoice->client_arca_cuit_pais_cliente
            ?? null;

        $idImpositivo = trim(
            (string) ($invoice->client_arca_id_impositivo ?? '')
        );

        if (! $paisDestino || $domicilioCliente === '') {
            $this->marcarError(
                $invoice,
                'No se puede emitir la Factura E: faltan el país de destino o el domicilio del cliente del exterior.'
            );

            return;
        }

        if (empty($cuitPaisCliente) && $idImpositivo === '') {
            $this->marcarError(
                $invoice,
                'No se puede emitir la Factura E: falta el identificador fiscal del comprador extranjero.'
            );

            return;
        }

        if (trim((string) $invoice->client_name) === '') {
            $this->marcarError(
                $invoice,
                'No se puede emitir la Factura E: falta el nombre del comprador extranjero.'
            );

            return;
        }

        if (
            $tipoComprobante !== 19
            && ! in_array($tipoComprobante, [20, 21], true)
        ) {
            $this->marcarError(
                $invoice,
                'El tipo de comprobante de exportación no está soportado.'
            );

            return;
        }

        /*
         * Las exportaciones utilizan un punto de venta específico
         * habilitado para WSFEXv1, independiente del de WSFEv1.
         */
        $puntoVentaExportacion = (int) (
            $this->config->punto_venta_exportacion ?? 0
        );

        if ($puntoVentaExportacion < 1) {
            $this->marcarError(
                $invoice,
                'Falta configurar el punto de venta habilitado para exportaciones WSFEX.'
            );

            return;
        }

        try {
            /*
             * El token WSAA debe solicitarse para WSFEX,
             * no se puede reutilizar el token de WSFE.
             */
            $wsfexClient = new WsaaClient(
                $this->config,
                'wsfex'
            );

            $credenciales = $wsfexClient->obtenerCredenciales();
        } catch (\Throwable $e) {
            $this->procesarErrorAutenticacion(
                $invoice,
                $automatico,
                $e
            );

            return;
        }

        $fechaComprobante = $this->fechaComprobante($momentoIntento);

        $total = round((float) $invoice->amount_paid, 2);

        if ($total <= 0) {
            $this->marcarError(
                $invoice,
                'No se puede emitir la Factura E porque el importe pagado debe ser mayor a cero.'
            );

            return;
        }

        /*
         * La aplicación cobra actualmente en pesos mediante
         * Mercado Pago. Por eso el comprobante utiliza PES y
         * cotización 1. Si más adelante se cobra en moneda extranjera,
         * esos valores deberán salir de una configuración específica.
         */
        $payload = [
            'invoiceId' => $invoice->id,
            'reintentoAutomatico' => $automatico,

            'token' => $credenciales['token'],
            'sign' => $credenciales['sign'],
            'cuit' => preg_replace(
                '/\D/',
                '',
                (string) config('arca.cuit')
            ),

            'puntoVenta' => $puntoVentaExportacion,
            'tipoComprobante' => $tipoComprobante,

            // Servicio exportado.
            'tipoExpo' => 2,

            'fecha' => $fechaComprobante->format('Ymd'),
            'permisoExistente' => '',

            'paisDestino' => (int) $paisDestino,
            'cliente' => trim((string) $invoice->client_name),
            'domicilioCliente' => $domicilioCliente,

            'monedaId' => 'PES',
            'monedaCotizacion' => 1,

            'importeTotal' => $total,
            'idiomaComprobante' => 1,
            'formaPago' => 'Mercado Pago',

            /*
             * WSFEX exige Fecha_pago para Factura E de servicios.
             * El comprobante se emite tras confirmar el pago.
             */
            'fechaPago' => (
                $invoice->paid_at
                    ? $invoice->paid_at->copy()
                    : $momentoIntento->copy()
            )->format('Ymd'),

            /*
             * Un servicio facturado como una unidad.
             * El importe del ítem debe coincidir con el total.
             */
            'items' => [[
                'proDescripcion' => mb_substr(
                    (string) $invoice->service_name,
                    0,
                    4000
                ),
                'proCantidad' => 1,
                'proUnidadMedida' => self::UNIDAD_MEDIDA_UNIDAD,
                'proPrecioUnitario' => $total,
                'proTotalItem' => $total,
            ]],
        ];

        if (! empty($cuitPaisCliente)) {
            $payload['cuitPaisCliente'] = (string) $cuitPaisCliente;
        }

        if ($idImpositivo !== '') {
            $payload['idImpositivo'] = $idImpositivo;
        }

        $respuesta = $this->enviarSolicitud(
            $invoice,
            $automatico,
            '/wsfe/solicitar-cae',
            $payload
        );

        if ($respuesta !== null) {
            $this->procesarRespuesta(
                $invoice,
                $respuesta,
                $tipoComprobante
            );
        }
    }

    /**
     * Realiza el POST a la API intermedia y gestiona errores transitorios.
     */
    private function enviarSolicitud(
        Invoice $invoice,
        bool $automatico,
        string $endpoint,
        array $payload
    ): ?Response {
        try {
            $respuesta = Http::withHeaders([
                'X-Internal-Api-Key' => config('arca.api_key'),
            ])
                ->timeout(30)
                ->post(
                    rtrim((string) config('arca.api_url'), '/')
                        . $endpoint,
                    $payload
                );
        } catch (ConnectionException $e) {
            $this->registrarFalloTransitorio(
                $invoice,
                'Error de conexión con la API ARCA: ' . $e->getMessage(),
                $automatico
            );

            return null;
        }

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

            return null;
        }

        return $respuesta;
    }

    private function procesarErrorAutenticacion(
        Invoice $invoice,
        bool $automatico,
        \Throwable $e
    ): void {
        $mensaje = 'Error al autenticar contra WSAA: ' . $e->getMessage();

        if ($this->esErrorTransitorio($e->getMessage())) {
            $this->registrarFalloTransitorio(
                $invoice,
                $mensaje,
                $automatico
            );

            return;
        }

        $this->marcarError($invoice, $mensaje);
    }

    private function alicuotaIdParaPorcentaje(float $porcentaje): int
    {
        return self::ALICUOTAS_AFIP[$porcentaje]
            ?? self::ALICUOTAS_AFIP[21];
    }

    private function fechaComprobante(Carbon $momentoIntento): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d',
            $momentoIntento
                ->copy()
                ->setTimezone('America/Argentina/Buenos_Aires')
                ->toDateString()
        )->startOfDay();
    }

    private function procesarRespuesta(
        Invoice $invoice,
        Response $respuesta,
        int $tipoComprobante
    ): void {
        $datos = $respuesta->json() ?? [];

        if (
            $respuesta->successful()
            && ($datos['exito'] ?? false)
        ) {
            $puntoVentaUtilizado = ComprobanteResolver::esExportacion(
                $tipoComprobante
            )
                ? $this->config->punto_venta_exportacion
                : $this->config->punto_venta;

            $invoice->update([
                'arca_status' => 'aprobado',
                'arca_cae' => $datos['cae'],
                'arca_cae_expires_at' => $this->convertirFechaCae(
                    $datos['caeVencimiento'] ?? null
                ),
                'arca_invoice_type' => $tipoComprobante,
                'arca_point_of_sale' => $puntoVentaUtilizado,
                'arca_invoice_number' => $datos['numeroComprobante'],
                'arca_retry_attempts' => 0,
                'arca_retry_at' => null,
            ]);

            try {
                app(\App\Services\InvoiceReceiptSender::class)
                    ->sendInBackground($invoice->fresh());
            } catch (\Throwable $e) {
                Log::error(
                    'No se pudo enviar el PDF de la factura.',
                    [
                        'invoice_id' => $invoice->id,
                        'message' => $e->getMessage(),
                    ]
                );
            }

            return;
        }

        $mensaje = collect($datos['errores'] ?? [])
            ->pluck('mensaje')
            ->filter()
            ->implode(' | ');

        if ($mensaje === '' && isset($datos['error'])) {
            $mensaje = (string) $datos['error'];
        }

        if ($mensaje === '') {
            $mensaje = sprintf(
                'HTTP %s. Respuesta API ARCA: %s',
                $respuesta->status(),
                $respuesta->body()
            );
        }

        /*
         * Los errores funcionales de ARCA no se reintentan
         * automáticamente: requieren corregir los datos.
         */
        $this->marcarError($invoice, $mensaje);
    }

    private function convertirFechaCae(?string $fecha): ?Carbon
    {
        if (! $fecha) {
            return null;
        }

        return Carbon::createFromFormat('Ymd', $fecha)->startOfDay();
    }

    private function registrarFalloTransitorio(
        Invoice $invoice,
        string $mensaje,
        bool $automatico
    ): void {
        $intentosActuales = (int) ($invoice->arca_retry_attempts ?? 0);

        $maxIntentos = max(
            1,
            (int) config('arca.retry.max_attempts', 8)
        );

        if ($automatico) {
            $proximoIntento = $intentosActuales + 1;

            if ($proximoIntento > $maxIntentos) {
                $invoice->update([
                    'arca_status' => 'error: ' . $mensaje
                        . ' Se agotaron los reintentos automáticos.',
                    'arca_retry_attempts' => $proximoIntento,
                    'arca_retry_at' => null,
                ]);

                return;
            }

            $invoice->update([
                'arca_retry_attempts' => $proximoIntento,
            ]);
        } else {
            /*
             * Un intento manual no consume el contador automático.
             */
            $proximoIntento = max(1, $intentosActuales + 1);
        }

        $base = max(
            1,
            (int) config('arca.retry.delay_minutes', 10)
        );

        $maxBackoff = max(
            $base,
            (int) config('arca.retry.max_backoff_minutes', 120)
        );

        $delay = min(
            $base * (2 ** ($proximoIntento - 1)),
            $maxBackoff
        );

        $invoice->update([
            'arca_status' => 'error: ' . $mensaje
                . ' Reintento automático programado en '
                . $delay . ' minutos.',
            'arca_retry_at' => now()->addMinutes($delay),
        ]);
    }

    private function esErrorTransitorio(string $mensaje): bool
    {
        $mensaje = strtolower($mensaje);

        foreach ([
            '429',
            'too many requests',
            'timeout',
            'timed out',
            'connection',
            'temporarily unavailable',
            '502',
            '503',
            '504',
        ] as $indicador) {
            if (str_contains($mensaje, $indicador)) {
                return true;
            }
        }

        return false;
    }

    private function marcarError(
        Invoice $invoice,
        string $mensaje
    ): void {
        $invoice->update([
            'arca_status' => 'error: ' . $mensaje,
            'arca_retry_at' => null,
        ]);
    }
}