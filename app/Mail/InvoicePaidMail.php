<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu factura pagada: ' . $this->invoice->service_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-paid',
        );
    }

    /**
     * Adjunta el PDF de la factura (con CAE y QR).
     */
    public function attachments(): array
    {
        $service = app(InvoicePdfService::class);

        return [
            Attachment::fromData(
                fn () => $service->render($this->invoice),
                $service->filename($this->invoice)
            )->withMime('application/pdf'),
        ];
    }
}
