<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    /**
     * Muestra el formulario para introducir el código.
     */
    public function show()
    {
        return view('auth.verify-email-code');
    }

    /**
     * Genera y envía un código de verificación.
     */
    public function sendCode()
    {
        $startedAt = microtime(true);

        $user = Auth::user();

        /*
         * Si la cuenta ya está verificada, no necesitamos
         * generar ni enviar otro código.
         */
        if ($user->email_verified_at !== null) {
            return $this->timedRedirect(
                $startedAt,
                'dashboard',
                'La cuenta ya está verificada.'
            );
        }

        /*
         * Comprobamos el tiempo transcurrido desde el último envío.
         *
         * El límite está asociado a la cuenta, no a la IP.
         */
        if (
            $user->email_verification_sent_at !== null &&
            $user->email_verification_sent_at->addHour()->isFuture()
        ) {
            return $this->timedBack(
                $startedAt,
                'Todavía no puedes solicitar otro código.'
            );
        }

        /*
         * Eliminamos códigos anteriores.
         */
        EmailVerificationCode::where('user_id', $user->id)->delete();

        /*
         * Generamos un código de 6 dígitos.
         */
        $code = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        /*
         * Guardamos el código durante 5 minutos.
         */
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        /*
         * Enviamos el correo mediante Brevo.
         *
         * El cooldown de una hora solamente se activa
         * si el envío se realiza correctamente.
         */
        try {
            Mail::to($user->email)->send(
                new EmailVerificationCodeMail($code)
            );

            /*
             * Guardamos el momento del envío solamente después
             * de que el correo haya sido enviado correctamente.
             */
            $user->update([
                'email_verification_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            /*
             * Si el envío falla, eliminamos el código generado
             * para que no quede un código activo que el usuario
             * nunca recibió.
             */
            EmailVerificationCode::where('user_id', $user->id)->delete();

            /*
             * No mostramos detalles internos del error.
             * La respuesta mantiene el tiempo controlado.
             */
            return $this->timedBack(
                $startedAt,
                'No se pudo enviar el código. Inténtalo nuevamente.'
            );
        }

        return $this->timedRedirect(
            $startedAt,
            'email.verification.show',
            'Se ha enviado un código de verificación a tu correo.'
        );
    }

    /*
     * Comprueba el código introducido por el usuario.
     */
    public function verifyCode(\Illuminate\Http\Request $request)
    {
        $startedAt = microtime(true);

        $request->validate([
            'code' => [
                'required',
                'digits:6',
            ],
        ]);

        $user = Auth::user();

        /*
         * Buscamos el código activo de esta cuenta.
         */
        $verificationCode = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        /*
         * No revelamos si el código existe,
         * expiró o fue eliminado.
         */
        if (!$verificationCode) {
            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Comprobamos primero si el código ha expirado.
         *
         * Un código expirado no consume intentos.
         */
        if ($verificationCode->expires_at->isPast()) {
            $verificationCode->delete();

            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Comprobamos el límite de intentos.
         *
         * Cada código puede utilizarse como máximo 5 veces.
         */
        if ($verificationCode->attempts >= 5) {
            /*
             * Eliminamos el código una vez alcanzado
             * el límite para impedir nuevos intentos.
             */
            $verificationCode->delete();

            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Cada código introducido consume un intento,
         * independientemente de que sea correcto o incorrecto.
         */
        $verificationCode->increment('attempts');

        /*
         * Comprobamos el código utilizando una comparación
         * resistente a ataques de timing.
         */
        if (!hash_equals(
            $verificationCode->code,
            $request->code
        )) {
            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * El código es correcto.
         */
        $user->update([
            'email_verified_at' => now(),
        ]);

        /*
         * Eliminamos el código para impedir que pueda
         * utilizarse nuevamente.
         */
        $verificationCode->delete();

        return $this->timedRedirect(
            $startedAt,
            'dashboard',
            'Tu cuenta ha sido verificada correctamente.'
        );
    }

    /**
     * Aplica un tiempo de respuesta objetivo aleatorio
     * entre 2 y 2.5 segundos.
     */
    private function waitForResponse(float $startedAt): void
    {
        /*
         * Generamos el objetivo en microsegundos:
         *
         * 2.000.000 = 2 segundos
         * 2.500.000 = 2.5 segundos
         */
        $targetTime = random_int(
            2_000_000,
            2_500_000
        );

        $elapsed = (int) ((microtime(true) - $startedAt) * 1_000_000);

        $remaining = $targetTime - $elapsed;

        /*
         * Si la operación ya tardó más que el objetivo,
         * respondemos inmediatamente.
         */
        if ($remaining > 0) {
            usleep($remaining);
        }
    }

    /**
     * Redirección con tiempo de respuesta controlado.
     */
    private function timedRedirect(
        float $startedAt,
        string $route,
        string $message
    ) {
        $this->waitForResponse($startedAt);

        return redirect()
            ->route($route)
            ->with('success', $message);
    }

    /**
     * Redirección hacia atrás con tiempo de respuesta controlado.
     */
    private function timedBack(
        float $startedAt,
        string $message
    ) {
        $this->waitForResponse($startedAt);

        return back()->withErrors([
            'code' => $message,
        ]);
    }
}