<?php

namespace App\Http\Controllers;

use App\Mail\PasswordSetupCodeMail;
use App\Models\PasswordSetupCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PasswordSetupController extends Controller
{
    /**
     * Muestra el formulario para introducir el correo.
     */
    public function show()
    {
        return view('auth.password-setup');
    }

    /**
     * Genera y envía el código al correo.
     */
    public function sendCode(Request $request)
    {
        $startedAt = microtime(true);

        $request->validate([
            'email' => [
                'required',
                'email',
            ],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->timedBack(
                $startedAt,
                'No se pudo procesar la solicitud.'
            );
        }

        /*
         * Solo permitimos este proceso para cuentas creadas
         * con Google que todavía no tienen contraseña.
         */
        if (!$user->google_id || $user->password !== null) {
            return $this->timedBack(
                $startedAt,
                'No se pudo procesar la solicitud.'
            );
        }

        /*
         * Comprobamos el cooldown de envío.
         *
         * Solo existe si hubo un envío correctamente realizado.
         */
        if (
            $user->password_setup_sent_at !== null &&
            $user->password_setup_sent_at->addHour()->isFuture()
        ) {
            return $this->timedBack(
                $startedAt,
                'Todavía no puedes solicitar otro código.'
            );
        }

        /*
         * Eliminamos códigos anteriores.
         */
        PasswordSetupCode::where('user_id', $user->id)->delete();

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
        PasswordSetupCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        /*
         * Enviamos el código al correo.
         *
         * El cooldown solamente se activa si el envío
         * se realiza correctamente.
         */
        try {
            Mail::to($user->email)->send(
                new PasswordSetupCodeMail($code)
            );

            /*
             * Guardamos el momento del envío solamente
             * después de que el correo haya sido enviado
             * correctamente.
             */
            $user->update([
                'password_setup_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            /*
             * Si el envío falla, eliminamos el código generado
             * para que no quede un código activo que el usuario
             * nunca recibió.
             */
            PasswordSetupCode::where('user_id', $user->id)->delete();

            /*
             * No mostramos detalles internos del error.
             */
            return $this->timedBack(
                $startedAt,
                'No se pudo enviar el código. Inténtalo nuevamente.'
            );
        }

        return $this->timedRedirect(
            $startedAt,
            'password.setup.verify',
            'Se ha enviado un código a tu correo.'
        );
    }

    /**
     * Muestra el formulario para introducir el código.
     */
    public function showVerifyForm()
    {
        if (!session('email')) {
            return redirect()->route('password.setup');
        }

        return view('auth.add-password-verify');
    }

    /**
     * Comprueba el código enviado al correo.
     */
    public function verifyCode(Request $request)
    {
        $startedAt = microtime(true);

        $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'code' => [
                'required',
                'digits:6',
            ],

            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Comprobamos nuevamente que se trate de una cuenta
         * creada originalmente mediante Google y que todavía
         * no tenga contraseña.
         */
        if (!$user->google_id || $user->password !== null) {
            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Buscamos el código activo de esta cuenta.
         */
        $passwordSetupCode = PasswordSetupCode::where(
            'user_id',
            $user->id
        )
            ->latest()
            ->first();

        /*
         * No revelamos si el código existe o no.
         */
        if (!$passwordSetupCode) {
            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Comprobamos primero la expiración.
         *
         * Un código expirado no debe consumir intentos.
         */
        if ($passwordSetupCode->expires_at->isPast()) {
            $passwordSetupCode->delete();

            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Comprobamos el límite de intentos.
         */
        if ($passwordSetupCode->attempts >= 5) {
            /*
             * Eliminamos el código una vez alcanzado
             * el límite para impedir nuevos intentos.
             */
            $passwordSetupCode->delete();

            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * Incrementamos el contador antes de comprobar
         * si el código es correcto.
         */
        $passwordSetupCode->increment('attempts');

        /*
         * Comparamos el código mediante una comparación
         * resistente a diferencias de tiempo.
         */
        if (!hash_equals(
            $passwordSetupCode->code,
            $request->code
        )) {
            return $this->timedBack(
                $startedAt,
                'El código no es válido.'
            );
        }

        /*
         * El código es correcto.
         *
         * Añadimos únicamente la contraseña.
         * El resto de los datos de la cuenta permanecen intactos.
         */
        $user->update([
            'password' => $request->password,
        ]);

        /*
         * El código ya no puede volver a utilizarse.
         */
        $passwordSetupCode->delete();

        /*
         * Limpiamos los datos temporales de la sesión.
         */
        session()->forget([
            'email',
            'password_setup_verified',
            'password_setup_user_id',
        ]);

        return $this->timedRedirect(
            $startedAt,
            'login',
            'Contraseña añadida correctamente. Ahora puedes iniciar sesión con Google o con tu correo y contraseña.'
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

        $elapsed = (int) (
            (microtime(true) - $startedAt) * 1_000_000
        );

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
        ])->withInput();
    }
}