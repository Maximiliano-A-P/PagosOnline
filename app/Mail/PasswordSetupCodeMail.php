<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordSetupCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Código de verificación.
     */
    public string $code;

    /**
     * Crea una nueva instancia del mensaje.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Configura el asunto y el remitente del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código para configurar tu contraseña',
        );
    }

    /**
     * Define el contenido del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-setup-code',
        );
    }

    /**
     * Archivos adjuntos.
     */
    public function attachments(): array
    {
        return [];
    }
}