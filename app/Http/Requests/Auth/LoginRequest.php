<?php

namespace App\Http\Requests\Auth;

use App\Mail\LoginUnlockMail;
use App\Models\LoginUnlockToken;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        /*
         * Guardamos el momento exacto en que comienza
         * el proceso completo de autenticación.
         */
        $startedAt = microtime(true);

        /*
         * Comprobamos si la cuenta se encuentra bloqueada.
         */
        $this->ensureIsNotLocked($startedAt);

        /*
         * Intentamos autenticar al usuario.
         */
        if (! Auth::attempt(
            $this->only('email', 'password'),
            $this->boolean('remember')
        )) {
            /*
             * El intento falló.
             *
             * El contador y el bloqueo se gestionan
             * directamente sobre la cuenta del usuario.
             */
            $this->registerFailedAttempt();

            /*
             * Aplicamos el tiempo de respuesta controlado
             * también cuando la contraseña es incorrecta.
             */
            $this->waitForResponse($startedAt);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        /*
         * El login fue correcto.
         *
         * Reiniciamos el contador de intentos fallidos.
         */
        $user = Auth::user();

        $user->update([
            'login_attempts' => 0,
        ]);

        /*
         * Aplicamos exactamente el mismo mecanismo
         * de tiempo de respuesta cuando la contraseña
         * es correcta.
         */
        $this->waitForResponse($startedAt);
    }

    /**
     * Comprueba si la cuenta está actualmente bloqueada.
     *
     * @throws ValidationException
     */
    private function ensureIsNotLocked(float $startedAt): void
    {
        $user = \App\Models\User::where(
            'email',
            $this->string('email')
        )->first();

        /*
         * Si no existe una cuenta con ese correo,
         * dejamos que Auth::attempt() gestione el error.
         */
        if (!$user) {
            return;
        }

        /*
         * Si la cuenta tiene un bloqueo registrado,
         * comprobamos si todavía sigue vigente.
         */
        if ($user->login_locked_until !== null) {

            /*
             * El bloqueo ya terminó.
             *
             * Reiniciamos el contador y eliminamos
             * la fecha de bloqueo.
             */
            if ($user->login_locked_until->isPast()) {
                $user->update([
                    'login_attempts' => 0,
                    'login_locked_until' => null,
                ]);

                return;
            }

            /*
             * La cuenta todavía está bloqueada.
             *
             * El tiempo de respuesta se calcula desde
             * el comienzo de todo el proceso de login.
             */
            $this->waitForResponse($startedAt);

            throw ValidationException::withMessages([
                'email' => 'La cuenta está temporalmente bloqueada. Inténtalo nuevamente más tarde.',
            ]);
        }
    }

    /**
     * Registra un intento de inicio de sesión fallido.
     */
    private function registerFailedAttempt(): void
    {
        $user = \App\Models\User::where(
            'email',
            $this->string('email')
        )->first();

        /*
         * Si el correo no corresponde a una cuenta,
         * no almacenamos información.
         */
        if (!$user) {
            return;
        }

        /*
         * Incrementamos el contador de intentos fallidos.
         */
        $user->increment('login_attempts');

        /*
         * Si se alcanzan 20 intentos fallidos,
         * bloqueamos la cuenta durante una hora.
         */
        if ($user->login_attempts >= 20) {
            /*
             * Reiniciamos el contador para que,
             * una vez desbloqueada la cuenta,
             * pueda comenzar nuevamente desde cero.
             */
            $user->update([
                'login_attempts' => 0,
                'login_locked_until' => now()->addHour(),
            ]);

            /*
             * Eliminamos cualquier enlace de desbloqueo
             * anterior que todavía exista.
             */
            LoginUnlockToken::where(
                'user_id',
                $user->id
            )->delete();

            /*
             * Generamos un token aleatorio.
             *
             * El token original solamente se enviará por correo.
             * En la base de datos guardaremos su hash.
             */
            $token = bin2hex(random_bytes(32));

            LoginUnlockToken::create([
                'user_id' => $user->id,
                'token' => hash('sha256', $token),
                'expires_at' => now()->addHour(),
            ]);

            /*
             * Generamos el enlace que recibirá el usuario.
             */
            $unlockUrl = route('login.unlock', [
                'token' => $token,
            ]);

            /*
             * Enviamos el enlace al correo de la cuenta.
             *
             * Si el envío falla, mantenemos igualmente
             * el bloqueo de una hora.
             */
            try {
                Mail::to($user->email)->send(
                    new LoginUnlockMail($unlockUrl)
                );
            } catch (\Exception $e) {
                /*
                 * No revelamos detalles internos del error
                 * y no modificamos el bloqueo.
                 */
            }
        }
    }

    /**
     * Aplica un tiempo de respuesta objetivo aleatorio
     * entre 2 y 2.5 segundos.
     */
    private function waitForResponse(float $startedAt): void
    {
        /*
         * Generamos un objetivo aleatorio en microsegundos:
         *
         * 2.000.000 = 2 segundos
         * 2.500.000 = 2.5 segundos
         */
        $targetTime = random_int(
            2_000_000,
            2_500_000
        );

        /*
         * Calculamos cuánto tiempo ha transcurrido
         * desde el comienzo de la operación.
         */
        $elapsed = (int) (
            (microtime(true) - $startedAt) * 1_000_000
        );

        /*
         * Calculamos cuánto falta para alcanzar
         * el tiempo objetivo.
         */
        $remaining = $targetTime - $elapsed;

        /*
         * Si la operación ya tardó más que el objetivo,
         * respondemos inmediatamente.
         */
        if ($remaining > 0) {
            usleep($remaining);
        }
    }
}