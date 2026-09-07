<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Confiar en el proxy de Render.
         *
         * Render termina la conexión HTTPS en su balanceador
         * y reenvía el tráfico a nuestro contenedor como HTTP.
         * Sin esto, Laravel cree que la conexión no es segura
         * y genera URLs de assets, cookies y redirects con
         * http:// en vez de https://.
         */
        $middleware->trustProxies(at: '*');

        /*
         * Alias para el middleware de administrador.
         *
         * Luego podremos proteger rutas utilizando:
         *
         * ->middleware('admin')
         */
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        /*
         * Excluir la ruta del webhook de Mercado Pago
         * de la verificación CSRF.
         */
        $middleware->validateCsrfTokens(except: [
            'mercadopago/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();