<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Webhook\WebhookSignatureValidator;

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

            Log::error(
                'Error de API de Mercado Pago al crear Preference.',
                [
                    'status_code' =>
                        $e->getApiResponse()->getStatusCode(),

                    'response' =>
                        $e->getApiResponse()->getContent(),

                    'invoice_id' =>
                        $invoice->id,
                ]
            );

            throw $e;

        } catch (\Throwable $e) {

            Log::error(
                'Error inesperado al crear Preference de Mercado Pago.',
                [
                    'message' =>
                        $e->getMessage(),

                    'invoice_id' =>
                        $invoice->id,

                    'exception' =>
                        get_class($e),
                ]
            );

            throw $e;
        }
    }

    /**
     * Consulta un pago directamente a Mercado Pago.
     */
    public function getPayment(string $paymentId)
    {
        $client = new PaymentClient();

        try {
            return $client->get(
                (int) $paymentId
            );

        } catch (MPApiException $e) {

            Log::error(
                'Error de API de Mercado Pago al consultar Payment.',
                [
                    'status_code' =>
                        $e->getApiResponse()->getStatusCode(),

                    'response' =>
                        $e->getApiResponse()->getContent(),

                    'payment_id' =>
                        $paymentId,
                ]
            );

            throw $e;

        } catch (\Throwable $e) {

            Log::error(
                'Error inesperado al consultar el pago de Mercado Pago.',
                [
                    'message' =>
                        $e->getMessage(),

                    'payment_id' =>
                        $paymentId,

                    'exception' =>
                        get_class($e),
                ]
            );

            throw $e;
        }
    }

    /**
     * Valida la firma enviada por Mercado Pago
     * en una notificación Webhook.
     *
     * Utiliza el validador oficial incluido
     * en el SDK de Mercado Pago.
     */
    public function validateWebhookSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId
    ): bool {
        $secret = config(
            'services.mercadopago.webhook_secret'
        );

        if (
            empty($secret)
            || empty($xSignature)
            || empty($xRequestId)
            || empty($dataId)
        ) {
            return false;
        }

        try {
            WebhookSignatureValidator::validate(
                $xSignature,
                $xRequestId,
                $dataId,
                $secret
            );

            return true;

        } catch (\Throwable $e) {

            Log::warning(
                'Firma de Webhook de Mercado Pago inválida.',
                [
                    'data_id' =>
                        $dataId,

                    'request_id' =>
                        $xRequestId,

                    'exception' =>
                        get_class($e),
                ]
            );

            return false;
        }
    }
}

