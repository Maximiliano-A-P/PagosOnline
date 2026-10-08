<?php

namespace App\Services;

use App\Mail\InvoicePaidMail;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía por correo el PDF de una factura pagada y autorizada (CAE).
 *
 * Destinatarios: los usuarios vinculados al cliente y el email de
 * contacto del cliente (sin repetir).
 */
class InvoiceReceiptSender
{
    public function send(Invoice $invoice): void
    {
        if ($invoice->payment_status !== 'paid' || empty($invoice->arca_cae)) {
            return;
        }

        $recipients = $this->recipients($invoice);

        if (empty($recipients)) {
            Log::info('Factura sin destinatarios para enviar el PDF.', [
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        /*
         * "Reclamo" atómico: solo un proceso puede pasar de
         * receipt_sent_at = NULL a una fecha. Así, si el webhook y
         * un reintento coinciden, el correo sale una sola vez.
         */
        $claimed = Invoice::query()
            ->whereKey($invoice->id)
            ->whereNull('receipt_sent_at')
            ->update(['receipt_sent_at' => now()]);

        if (!$claimed) {
            return;
        }

        try {
            Mail::to($recipients)->send(new InvoicePaidMail($invoice->fresh()));
        } catch (\Throwable $e) {
            // Si el envío falla, se libera para poder reintentarlo.
            Invoice::query()
                ->whereKey($invoice->id)
                ->update(['receipt_sent_at' => null]);

            Log::error('No se pudo enviar el correo de la factura.', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return string[]
     */
    private function recipients(Invoice $invoice): array
    {
        $client = Client::where('document', $invoice->client_document)->first();

        if (!$client) {
            return [];
        }

        return $client->users()
            ->pluck('email')
            ->push($client->email)
            ->filter()
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
