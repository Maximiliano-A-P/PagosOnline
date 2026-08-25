<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Permite acceder al panel únicamente
     * a usuarios con permisos administrativos.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /*
         * Si no hay un usuario autenticado,
         * no permitimos el acceso.
         */
        if (!auth()->check()) {
            abort(403);
        }

        /*
         * Comprobamos el permiso administrativo.
         */
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        return $next($request);
    }
}
