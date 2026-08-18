<?php

namespace App\Http\Controllers;

use App\Models\LoginUnlockToken;
use Illuminate\Support\Facades\DB;

class LoginUnlockController extends Controller
{
    /**
     * Desbloquea una cuenta mediante el enlace enviado
     * al correo electrónico del usuario.
     */
    public function unlock(string $token)
    {
        $tokenHash = hash('sha256', $token);

        $unlockToken = LoginUnlockToken::where(
            'token',
            $tokenHash
        )->first();

        /*
         * No revelamos si el token existe,
         * fue utilizado o nunca existió.
         */
        if (!$unlockToken) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'El enlace de desbloqueo no es válido.',
                ]);
        }

        /*
         * Comprobamos si el enlace todavía está vigente.
         */
        if ($unlockToken->expires_at->isPast()) {
            $unlockToken->delete();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'El enlace de desbloqueo ha expirado.',
                ]);
        }

        $user = $unlockToken->user;

        /*
         * Eliminamos el bloqueo y reiniciamos
         * el contador de intentos.
         */
        $user->update([
            'login_attempts' => 0,
            'login_locked_until' => null,
        ]);

        /*
         * El enlace es de un solo uso.
         */
        $unlockToken->delete();

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Tu cuenta ha sido desbloqueada. Ya puedes volver a iniciar sesión.'
            );
    }
}