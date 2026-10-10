<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Genera el PDF de una factura a partir de su estado actual.
 *
 * - Pendiente: detalle de la factura con estado "pendiente de pago".
 * - Pagada: sello PAGADA + recibo (fecha, medio y monto).
 * - Pagada con CAE: además datos de ARCA y QR del comprobante.
 *
 * El PDF no se guarda en disco: se arma cada vez, así nunca queda
 * desactualizado respecto de la base de datos.
 */
class InvoicePdfService
{
    /**
     * URL base del QR de comprobantes (formato oficial ARCA/AFIP).
     */
    private const QR_URL = 'https://www.afip.gob.ar/fe/qr/?p=';

    private const TIPOS_COMPROBANTE = [
        1  => 'A',
        6  => 'B',
        11 => 'C',
    ];

    private const CONDICIONES_IVA = [
        1  => 'IVA Responsable Inscripto',
        4  => 'IVA Sujeto Exento',
        5  => 'Consumidor Final',
        6  => 'Responsable Monotributo',
        7  => 'Sujeto No Categorizado',
        13 => 'Monotributista Social',
        15 => 'IVA No Alcanzado',
        16 => 'Monotributo Trabajador Independiente Promovido',
    ];

    /**
     * Devuelve el contenido binario del PDF.
     */
    public function render(Invoice $invoice): string
    {
        return Pdf::loadView('pdf.invoice', $this->data($invoice))
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
     * Datos que usa la vista del PDF.
     */
    private function data(Invoice $invoice): array
    {
        $pagada = $invoice->payment_status === 'paid';
        $conCae = !empty($invoice->arca_cae);

        $total = round($invoice->montoACobrar(), 2);
        $tasa = (float) ($invoice->tax_percentage ?? 0);
        $neto = round($total / (1 + $tasa / 100), 2);
        $iva = round($total - $neto, 2);

        /*
         * Datos de contacto del cliente (teléfono, email, dirección):
         * la factura solo guarda nombre, documento, CUIT e IVA, el
         * resto se toma de la ficha actual del cliente.
         */
        $client = Client::where('document', $invoice->client_document)->first();

        $metodo = $invoice->payment_method;
        if ($metodo === 'mercadopago') {
            $metodo = 'Mercado Pago';
        }

        return [
            'invoice' => $invoice,
            'pagada' => $pagada,
            'conCae' => $conCae,
            'letra' => self::TIPOS_COMPROBANTE[$invoice->arca_invoice_type] ?? null,
            'numero' => $this->numeroComprobante($invoice),
            'neto' => $neto,
            'iva' => $iva,
            'tasa' => $tasa,
            'total' => $total,
            'condicionIva' => self::CONDICIONES_IVA[
                $invoice->client_iva_condition ?: 5
            ] ?? 'Consumidor Final',
            'metodoPago' => $metodo,
            'client' => $client,
            'codigoIva' => (int) ($invoice->client_iva_condition ?: 5),
            'qr' => ($pagada && $conCae) ? $this->qrDataUri($invoice) : null,
        ];
    }

    /**
     * Número en formato 00001-00000123.
     */
    private function numeroComprobante(Invoice $invoice): ?string
    {
        if (!$invoice->arca_point_of_sale || !$invoice->arca_invoice_number) {
            return null;
        }

        return str_pad((string) $invoice->arca_point_of_sale, 5, '0', STR_PAD_LEFT)
            . '-'
            . str_pad((string) $invoice->arca_invoice_number, 8, '0', STR_PAD_LEFT);
    }

    /**
     * QR del comprobante según la especificación de ARCA/AFIP:
     * JSON en base64 dentro del parámetro "p" de la URL.
     */
    private function qrDataUri(Invoice $invoice): string
    {
        $fecha = $invoice->arca_last_attempt_at
            ?? $invoice->paid_at
            ?? $invoice->issued_at;

        $payload = [
            'ver' => 1,
            'fecha' => $fecha->format('Y-m-d'),
            'cuit' => (int) config('arca.cuit'),
            'ptoVta' => (int) $invoice->arca_point_of_sale,
            'tipoCmp' => (int) $invoice->arca_invoice_type,
            'nroCmp' => (int) $invoice->arca_invoice_number,
            'importe' => round((float) $invoice->amount_paid, 2),
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoDocRec' => (int) ($invoice->client_document_type ?? 96),
            'nroDocRec' => (int) $invoice->client_document,
            'tipoCodAut' => 'E',
            'codAut' => (int) $invoice->arca_cae,
        ];

        $url = self::QR_URL . base64_encode(
            json_encode($payload, JSON_UNESCAPED_SLASHES)
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