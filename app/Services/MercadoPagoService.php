<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoService
{
    /**
     * Configura el SDK de Mercado Pago.
     */
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(
            config('services.mercadopago.access_token')
        );
    }

    /**
     * Crea una preferencia de pago para una factura.
     */
    public function createPreference(Invoice $invoice)
    {
        $client = new PreferenceClient();

        try {
            $preference = $client->create([
                'items' => [
                    [
                        'id' => (string) $invoice->id,
                        'title' => $invoice->service_name,
                        'quantity' => 1,
                        'currency_id' => 'ARS',
                        'unit_price' => (float) $invoice->price,
                    ],
                ],

                /*
                 * Permite relacionar posteriormente el pago
                 * recibido desde Mercado Pago con nuestra factura.
                 */
                'external_reference' => (string) $invoice->id,

                /*
                 * URL pública utilizada por Mercado Pago
                 * para enviar las notificaciones.
                 *
                 * Debe ser HTTPS y accesible desde Internet.
                 */
                'notification_url' => config(
                    'services.mercadopago.webhook_url'
                ),

                /*
                 * URLs utilizadas por Mercado Pago para
                 * devolver al usuario a nuestro sistema.
                 */
                'back_urls' => [
                    'success' => route('dashboard'),
                    'failure' => route('dashboard'),
                    'pending' => route('dashboard'),
                ],

                'auto_return' => 'approved',
            ]);

            return $preference;

        } catch (MPApiException $e) {

            /*
             * Mercado Pago devolvió un error de API.
             *
             * Registramos el código HTTP y el contenido
             * de la respuesta para poder diagnosticar
             * el problema desde los logs de Laravel.
             */
            Log::error(
                'Error de API de Mercado Pago al crear Preference.',
                [
                    'status_code' => $e->getApiResponse()->getStatusCode(),
                    'response' => $e->getApiResponse()->getContent(),
                    'invoice_id' => $invoice->id,
                ]
            );

            throw $e;

        } catch (\Throwable $e) {

            /*
             * Capturamos cualquier otro error inesperado
             * relacionado con la creación de la Preference.
             */
            Log::error(
                'Error inesperado al crear Preference de Mercado Pago.',
                [
                    'message' => $e->getMessage(),
                    'invoice_id' => $invoice->id,
                    'exception' => get_class($e),
                ]
            );

            throw $e;
        }
    }

    /**
     * Consulta un pago directamente a Mercado Pago.
     *
     * Esta consulta se utiliza después de recibir
     * una notificación Webhook.
     */
    public function getPayment(string $paymentId)
    {
        $client = new PaymentClient();

        try {
            return $client->get(
                (int) $paymentId
            );

        } catch (MPApiException $e) {

            /*
             * Mercado Pago devolvió un error al consultar
             * el pago.
             */
            Log::error(
                'Error de API de Mercado Pago al consultar Payment.',
                [
                    'status_code' => $e->getApiResponse()->getStatusCode(),
                    'response' => $e->getApiResponse()->getContent(),
                    'payment_id' => $paymentId,
                ]
            );

            throw $e;

        } catch (\Throwable $e) {

            /*
             * Capturamos cualquier otro error inesperado.
             */
            Log::error(
                'Error inesperado al consultar Payment de Mercado Pago.',
                [
                    'message' => $e->getMessage(),
                    'payment_id' => $paymentId,
                    'exception' => get_class($e),
                ]
            );

            throw $e;
        }
    }

    /**
     * Valida la firma enviada por Mercado Pago
     * en una notificación Webhook.
     */
    public function validateWebhookSignature(
        string $xSignature,
        string $xRequestId,
        string $dataId
    ): bool {
        $secret = config(
            'services.mercadopago.webhook_secret'
        );

        /*
         * Sin Webhook Secret no podemos validar
         * la autenticidad de la notificación.
         */
        if (!$secret) {
            return false;
        }

        /*
         * Extraemos los valores de la firma.
         *
         * Formato esperado:
         *
         * ts=...,v1=...
         */
        $parts = [];

        foreach (explode(',', $xSignature) as $part) {

            [$key, $value] = array_pad(
                explode('=', $part, 2),
                2,
                null
            );

            if (
                $key !== null
                && $value !== null
            ) {
                $parts[trim($key)] = trim($value);
            }
        }

        /*
         * Una firma válida debe contener
         * timestamp y hash.
         */
        if (
            empty($parts['ts'])
            || empty($parts['v1'])
        ) {
            return false;
        }

        /*
         * Manifest utilizado por Mercado Pago
         * para generar la firma HMAC.
         */
        $manifest =
            'id:' . strtolower($dataId) .
            ';request-id:' . $xRequestId .
            ';ts:' . $parts['ts'] .
            ';';

        /*
         * Generamos nuestra propia firma utilizando
         * la Webhook Secret.
         */
        $expectedSignature = hash_hmac(
            'sha256',
            $manifest,
            $secret
        );

        /*
         * Comparación segura contra ataques de timing.
         */
        return hash_equals(
            $expectedSignature,
            $parts['v1']
        );
    }
}