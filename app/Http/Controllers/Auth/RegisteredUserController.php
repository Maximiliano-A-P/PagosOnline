<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCodeMail;
use App\Mail\PasswordSetupCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\PasswordSetupCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        /*
         * Buscamos si ya existe una cuenta
         * con este correo.
         */
        $existingUser = User::where(
            'email',
            $request->email
        )->first();

        /*
         * CASO 1:
         *
         * Ya existe una cuenta creada mediante Google
         * y todavía no tiene contraseña.
         *
         * No creamos otra cuenta.
         * Iniciamos el proceso para agregar una contraseña
         * a la cuenta existente.
         */
        if (
            $existingUser &&
            $existingUser->google_id &&
            $existingUser->password === null
        ) {
            /*
             * Eliminamos códigos anteriores.
             */
            PasswordSetupCode::where(
                'user_id',
                $existingUser->id
            )->delete();

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
             * El código será válido durante 5 minutos.
             */
            PasswordSetupCode::create([
                'user_id' => $existingUser->id,
                'code' => $code,
                'expires_at' => now()->addMinutes(5),
            ]);

            /*
             * Enviamos el código al correo asociado
             * a la cuenta de Google.
             */
            Mail::to($existingUser->email)->send(
                new PasswordSetupCodeMail($code)
            );

            /*
             * Guardamos temporalmente el correo en sesión.
             */
            session([
                'email' => $existingUser->email,
            ]);

            /*
             * El password introducido en el formulario
             * NO se guarda.
             *
             * Primero debe verificarse el código.
             */
            return redirect()
                ->route('password.setup.verify')
                ->with(
                    'success',
                    'Se ha enviado un código de verificación a tu correo.'
                );
        }

        /*
         * CASO 2:
         *
         * Ya existe una cuenta que no es una cuenta Google
         * sin contraseña.
         *
         * No permitimos crear otra cuenta con el mismo correo.
         */
        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => 'El correo electrónico ya está registrado.',
            ]);
        }

        /*
         * CASO 3:
         *
         * El correo no existe.
         *
         * Creamos una cuenta tradicional.
         *
         * email_verified_at queda NULL hasta que
         * el usuario introduzca correctamente el código.
         */
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        /*
         * Generamos el código de verificación.
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
         * Registramos cuándo se envió el código.
         *
         * Este valor se utilizará para controlar
         * el cooldown de 1 hora para solicitar otro.
         */
        $user->update([
            'email_verification_sent_at' => now(),
        ]);

        /*
         * Enviamos automáticamente el código
         * al correo recién registrado.
         */
        Mail::to($user->email)->send(
            new EmailVerificationCodeMail($code)
        );

        /*
         * Iniciamos sesión automáticamente.
         */
        Auth::login($user);

        /*
         * Entramos directamente al dashboard.
         *
         * El dashboard mostrará el aviso de que
         * la cuenta todavía no está verificada.
         */
        return redirect()->route('dashboard');
    }
}