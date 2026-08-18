<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirige al usuario hacia Google.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Recibe la respuesta de Google y gestiona el login/registro.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // No existe una cuenta con ese email.
                // Creamos una nueva cuenta vinculada a Google.
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'email_verified_at' => now(),
                ]);
            } elseif (!$user->google_id) {
                // Ya existe una cuenta tradicional con ese email.
                // Vinculamos Google a esa misma cuenta.
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]);
            }

            // Iniciamos sesión automáticamente en la plataforma.
            Auth::login($user);

            request()->session()->regenerate();

            return redirect()->route('dashboard');

        } catch (Exception $e) {
            return redirect()
                ->route('login')
                ->with('error', 'Ocurrió un error al iniciar sesión con Google. Inténtalo nuevamente.');
        }
    }
}