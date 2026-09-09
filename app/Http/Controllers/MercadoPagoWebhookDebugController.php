<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookDebugController extends Controller
{
    /**
     * Endpoint temporal para inspeccionar exactamente
     * qué datos está enviando Mercado Pago.
     *
     * IMPORTANTE:
     *
     * - No valida x-signature.
     * - No consulta Mercado Pago.
     * - No modifica facturas.
     * - No cambia estados de pago.
     * - No procesa ninguna operación comercial.
     *
     * Solo registra temporalmente la petición recibida.
     */
    public function handle(Request $request): JsonResponse
    {
        /*
         * Obtener el body crudo exactamente como llegó.
         */
        $rawBody = $request->getContent();

        /*
         * Obtener todos los parámetros de la query string.
         *
         * Ejemplo:
         *
         * ?data.id=123456&type=payment
         */
        $query = $request->query();


        /*
         * Obtener todos los headers recibidos.
         *
         * Aquí podremos ver exactamente:
         *
         * x-signature
         * x-request-id
         *
         * además del resto de headers enviados.
         */
        $headers = $request->headers->all();


        /*
         * Registrar la petición completa.
         *
         * ADVERTENCIA:
         *
         * Esta ruta es temporal.
         *
         * El x-signature se registra completo
         * únicamente para esta investigación.
         *
         * NUNCA se registra:
         *
         * MERCADOPAGO_WEBHOOK_SECRET
         * MERCADOPAGO_ACCESS_TOKEN
         */
        Log::channel('stderr')->info(
            'MERCADO PAGO - WEBHOOK DEBUG RECIBIDO',
            [
                'method' => $request->method(),

                'full_url' => $request->fullUrl(),

                'query' => $query,

                'headers' => $headers,

                'raw_body' => $rawBody,

                'parsed_body' => $request->all(),

                'x_signature' =>
                    $request->header('x-signature'),

                'x_request_id' =>
                    $request->header('x-request-id'),

                'data_id' =>
                    $request->query('data.id')
                    ?? $request->input('data.id')
                    ?? $request->query('data_id'),

                'type' =>
                    $request->query('type')
                    ?? $request->input('type'),
            ]
        );


        /*
         * Respondemos inmediatamente con HTTP 200.
         *
         * NO hacemos ninguna otra operación.
         */
        return response()->json([
            'ok' => true,
            'message' => 'Webhook recibido para diagnóstico.',
        ], 200);
    }
}