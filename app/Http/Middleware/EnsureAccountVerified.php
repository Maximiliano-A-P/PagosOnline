<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que la cuenta tenga el correo verificado (código de 6 dígitos).
 *
 * Si no lo está, redirige a la pantalla de verificación.
 */
class EnsureAccountVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->email_verified_at === null) {
            return redirect()
                ->route('email.verification.show')
                ->with(
                    'error',
                    'Tenés que verificar tu correo antes de pagar una factura.'
                );
        }

        return $next($request);
    }
}