<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginUnlockMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Enlace utilizado para desbloquear la cuenta.
     */
    public string $unlockUrl;

    /**
     * Crea una nueva instancia del mensaje.
     */
    public function __construct(string $unlockUrl)
    {
        $this->unlockUrl = $unlockUrl;
    }

    /**
     * Configura el asunto del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Desbloqueo de tu cuenta',
        );
    }

    /**
     * Define el contenido del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.login-unlock',
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