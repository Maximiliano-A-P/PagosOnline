<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\Arca\ComprobanteResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Genera el PDF de una factura a partir de su estado actual.
 *
 * Prepara comprobantes nacionales y de exportación, conserva
 * los datos históricos del comprador y genera el QR cuando
 * el comprobante dispone de la información necesaria.
 */
class InvoicePdfService
{
    private const QR_URL = 'https://www.afip.gob.ar/fe/qr/?p=';

    private const TIPOS_COMPROBANTE = [
        1 => 'A',
        2 => 'A',
        3 => 'A',
        4 => 'A',
        5 => 'A',

        6 => 'B',
        7 => 'B',
        8 => 'B',
        9 => 'B',
        10 => 'B',

        11 => 'C',
        12 => 'C',
        13 => 'C',
        15 => 'C',

        19 => 'E',
        20 => 'E',
        21 => 'E',

        // Comprobantes A sujetos a retención.
        51 => 'A',
        52 => 'A',
        53 => 'A',
    ];

    private const DESCRIPCIONES_COMPROBANTE = [
        1 => 'Factura A',
        2 => 'Nota de débito A',
        3 => 'Nota de crédito A',
        4 => 'Recibo A',
        5 => 'Nota de venta al contado A',

        6 => 'Factura B',
        7 => 'Nota de débito B',
        8 => 'Nota de crédito B',
        9 => 'Recibo B',
        10 => 'Nota de venta al contado B',

        11 => 'Factura C',
        12 => 'Nota de débito C',
        13 => 'Nota de crédito C',
        15 => 'Recibo C',

        19 => 'Factura E',
        20 => 'Nota de débito E',
        21 => 'Nota de crédito E',

        51 => 'Factura A sujeta a retención',
        52 => 'Nota de débito A sujeta a retención',
        53 => 'Nota de crédito A sujeta a retención',
    ];

    private const CONDICIONES_IVA = [
        1 => 'IVA Responsable Inscripto',
        4 => 'IVA Sujeto Exento',
        5 => 'Consumidor Final',
        6 => 'Responsable Monotributo',
        7 => 'Sujeto No Categorizado',
        9 => 'Cliente del exterior',
        13 => 'Monotributista Social',
        15 => 'IVA No Alcanzado',
        16 => 'Monotributo Trabajador Independiente Promovido',
    ];

    /**
     * Devuelve el contenido binario del PDF.
     */
    public function render(Invoice $invoice): string
    {
        return Pdf::loadView(
            'pdf.invoice',
            $this->data($invoice)
        )
            ->setPaper('a4')
            ->output();
    }

    /**
     * Nombre de archivo sugerido.
     */
    public function filename(Invoice $invoice): string
    {
        $numero = $this->numeroComprobante($invoice);

        return $numero
            ? "factura-{$numero}.pdf"
            : "factura-{$invoice->id}.pdf";
    }

    /**
     * Datos que utilizan las vistas del PDF.
     */
    private function data(Invoice $invoice): array
    {
        $pagada = $invoice->payment_status === 'paid';
        $conCae = ! empty($invoice->arca_cae);

        $tipoComprobante = (int) ($invoice->arca_invoice_type ?? 0);

        $esExportacion = ComprobanteResolver::esExportacion(
            $tipoComprobante
        );

        $esAConLeyendaRetencion =
            ComprobanteResolver::esAConLeyendaRetencion(
                $tipoComprobante
            );

        $total = round(
            (float) $invoice->montoACobrar(),
            2
        );

        $tasa = $esExportacion
            ? 0.0
            : (float) ($invoice->tax_percentage ?? 0);

        /*
         * En las exportaciones no se muestra el IVA nacional
         * como si formara parte de una factura A, B o C.
         *
         * El importe total del servicio se presenta como neto.
         */
        if ($esExportacion) {
            $neto = $total;
            $iva = 0.0;
        } else {
            $neto = round(
                $total / (1 + $tasa / 100),
                2
            );

            $iva = round($total - $neto, 2);
        }

        /*
         * La ficha del cliente se utiliza para los datos de contacto.
         * Los datos fiscales de la factura se toman del snapshot guardado
         * en Invoice, no de los datos fiscales actuales del cliente.
         */
        $client = Client::where(
            'document',
            $invoice->client_document
        )->first();

        $metodo = $invoice->payment_method;

        if ($metodo === 'mercadopago') {
            $metodo = 'Mercado Pago';
        }

        $condicionIvaCodigo = (int) (
            $invoice->client_iva_condition ?: 5
        );

        $comprobanteLetra =
            self::TIPOS_COMPROBANTE[$tipoComprobante] ?? null;

        $leyendaComprobante = $esAConLeyendaRetencion
            ? 'OPERACIÓN SUJETA A RETENCIÓN'
            : null;

        $fechaComprobante = $invoice->arca_last_attempt_at
            ? $invoice->arca_last_attempt_at
                ->copy()
                ->setTimezone('America/Argentina/Buenos_Aires')
            : $invoice->issued_at;

        $identificadorFiscalExtranjero = $esExportacion
            ? (
                $invoice->client_arca_cuit_pais_cliente
                ?: $invoice->client_arca_id_impositivo
            )
            : null;

        return [
            // Objetos principales.
            'invoice' => $invoice,
            'client' => $client,

            // Estado del comprobante.
            'pagada' => $pagada,
            'conCae' => $conCae,
            'esExportacion' => $esExportacion,
            'esAConLeyendaRetencion' => $esAConLeyendaRetencion,

            // Identificación fiscal.
            'letra' => $comprobanteLetra,
            'tipoComprobante' => $tipoComprobante ?: null,
            'comprobanteNombre' =>
                self::DESCRIPCIONES_COMPROBANTE[$tipoComprobante]
                ?? 'Comprobante ARCA',
            'leyendaComprobante' => $leyendaComprobante,
            'numero' => $this->numeroComprobante($invoice),
            'fechaComprobante' => $fechaComprobante,

            // Importes.
            'neto' => $neto,
            'iva' => $iva,
            'tasa' => $tasa,
            'total' => $total,

            // Moneda configurada actualmente para la aplicación.
            'monedaCodigo' => 'PES',
            'simboloMoneda' => '$',

            // Condición fiscal del receptor.
            'codigoIva' => $condicionIvaCodigo,
            'condicionIva' =>
                self::CONDICIONES_IVA[$condicionIvaCodigo]
                ?? 'Condición fiscal no especificada',

            // Datos fiscales del comprador.
            'clientName' => $invoice->client_name,
            'clientDocument' => $esExportacion
                ? $identificadorFiscalExtranjero
                : $invoice->client_document,
            'clientDocumentLabel' => $esExportacion
                ? 'Identificador fiscal extranjero'
                : 'Documento',
            'clientCuit' => $esExportacion
                ? null
                : $invoice->client_cuit,
            'clientAddress' => $invoice->client_address
                ?: $client?->address,
            'clientDestinationCountry' =>
                $invoice->client_arca_destination_country,
            'clientForeignTaxId' => $identificadorFiscalExtranjero,
            'clientIdImpositivo' =>
                $invoice->client_arca_id_impositivo,

            // Pago.
            'metodoPago' => $metodo,

            // Código QR.
            'qr' => (
                $pagada
                && $conCae
                && $this->puedeGenerarQr($invoice)
            )
                ? $this->qrDataUri($invoice)
                : null,
        ];
    }

    /**
     * Indica si están presentes los datos mínimos para construir el QR.
     */
    private function puedeGenerarQr(Invoice $invoice): bool
    {
        return ! empty(config('arca.cuit'))
            && ! empty($invoice->arca_point_of_sale)
            && ! empty($invoice->arca_invoice_type)
            && ! empty($invoice->arca_invoice_number)
            && ! empty($invoice->arca_cae);
    }

    /**
     * Número del comprobante en formato 00001-00000123.
     */
    private function numeroComprobante(Invoice $invoice): ?string
    {
        if (
            ! $invoice->arca_point_of_sale
            || ! $invoice->arca_invoice_number
        ) {
            return null;
        }

        return str_pad(
            (string) $invoice->arca_point_of_sale,
            5,
            '0',
            STR_PAD_LEFT
        )
            . '-'
            . str_pad(
                (string) $invoice->arca_invoice_number,
                8,
                '0',
                STR_PAD_LEFT
            );
    }

    /**
     * Genera el QR usando el formato JSON codificado en Base64.
     *
     * Para comprobantes de exportación no se incorporan los campos
     * del documento argentino del receptor, porque no corresponden
     * al identificador fiscal extranjero.
     */
    private function qrDataUri(Invoice $invoice): string
    {
        $fecha = $invoice->arca_last_attempt_at
            ? $invoice->arca_last_attempt_at
                ->copy()
                ->setTimezone('America/Argentina/Buenos_Aires')
            : (
                $invoice->paid_at
                ?? $invoice->issued_at
            );

        $tipoComprobante = (int) $invoice->arca_invoice_type;

        $payload = [
            'ver' => 1,
            'fecha' => $fecha->format('Y-m-d'),
            'cuit' => (int) preg_replace(
                '/\D/',
                '',
                (string) config('arca.cuit')
            ),
            'ptoVta' => (int) $invoice->arca_point_of_sale,
            'tipoCmp' => $tipoComprobante,
            'nroCmp' => (int) $invoice->arca_invoice_number,
            'importe' => round(
                (float) $invoice->amount_paid,
                2
            ),
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoCodAut' => 'E',
            'codAut' => (int) $invoice->arca_cae,
        ];

        /*
         * En comprobantes nacionales se utiliza el mismo criterio
         * de tipo y número de documento que el emisor del comprobante.
         */
        if (! ComprobanteResolver::esExportacion($tipoComprobante)) {
            $documento = ComprobanteResolver::resolverDocumento(
                $tipoComprobante,
                (string) $invoice->client_document,
                $invoice->client_cuit
            );

            if (
                $documento['docTipo'] !== null
                && $documento['docNro'] !== null
                && $documento['docNro'] !== ''
            ) {
                $payload['tipoDocRec'] = (int) $documento['docTipo'];
                $payload['nroDocRec'] = (int) $documento['docNro'];
            }
        }

        $url = self::QR_URL . base64_encode(
            json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES
            )
        );

        $result = (new Builder(
            writer: new PngWriter(),
            data: $url,
            size: 220,
            margin: 0,
        ))->build();

        return $result->getDataUri();
    }
}