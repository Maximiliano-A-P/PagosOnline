<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
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
                'notification_url' => config(
                    'services.mercadopago.webhook_url'
                ),
                */
                'notification_url' => 'https://pagosonline.onrender.com/mercadopago/webhook-debug',

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
                    'status_code' => $e->getApiResponse()->getStatusCode(),
                    'response' => $e->getApiResponse()->getContent(),
                    'invoice_id' => $invoice->id,
                ]
            );

            throw $e;

        } catch (\Throwable $e) {

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
                    'status_code' => $e->getApiResponse()->getStatusCode(),
                    'response' => $e->getApiResponse()->getContent(),
                    'payment_id' => $paymentId,
                ]
            );

            throw $e;

        } catch (\Throwable $e) {

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
     *
     * Utiliza el validador oficial incluido en el SDK.
     *
     * Además, cuando la validación falla, se realiza
     * un diagnóstico paralelo del HMAC sin registrar
     * información secreta.
     */
    public function validateWebhookSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId
    ): bool {
        $secret = config(
            'services.mercadopago.webhook_secret'
        );

        Log::channel('stderr')->info(
            'MERCADO PAGO - INICIO VALIDACION FIRMA',
            [
                'has_signature' => !empty($xSignature),
                'has_request_id' => !empty($xRequestId),
                'has_data_id' => !empty($dataId),
                'secret_configured' => !empty($secret),
                'secret_length' => $secret
                    ? strlen($secret)
                    : 0,

                /*
                 * Huella de la clave secreta.
                 *
                 * No registra la clave.
                 */
                'secret_fingerprint' => $secret
                    ? substr(hash('sha256', $secret), 0, 12)
                    : null,
            ]
        );

        if (!$secret) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - WEBHOOK SECRET VACIO'
            );

            return false;
        }

        try {
            /*
             * Utilizamos primero el validador oficial
             * del SDK de Mercado Pago.
             */
            WebhookSignatureValidator::validate(
                $xSignature,
                $xRequestId,
                $dataId,
                $secret
            );

            Log::channel('stderr')->info(
                'MERCADO PAGO - FIRMA VALIDA'
            );

            return true;

        } catch (InvalidWebhookSignatureException $e) {

            /*
             * ------------------------------------------------------
             * DIAGNÓSTICO
             * ------------------------------------------------------
             *
             * Reproducimos exactamente el cálculo HMAC indicado
             * por Mercado Pago para saber si la firma recibida
             * coincide con la firma que calculamos localmente.
             */

            $parts = [];

            foreach (explode(',', (string) $xSignature) as $part) {
                $pieces = explode('=', $part, 2);

                if (count($pieces) !== 2) {
                    continue;
                }

                $key = strtolower(trim($pieces[0]));
                $value = trim($pieces[1]);

                if ($key === '' || $value === '') {
                    continue;
                }

                $parts[$key] = $value;
            }

            $timestamp = $parts['ts'] ?? null;
            $receivedHash = $parts['v1'] ?? null;

            /*
             * Construimos el mismo manifest que utiliza
             * el validador oficial:
             *
             * id:<data.id>;
             * request-id:<x-request-id>;
             * ts:<timestamp>;
             */
            $manifestParts = [];

            if (
                $dataId !== null
                && trim($dataId) !== ''
            ) {
                $manifestParts[] =
                    'id:' . trim($dataId);
            }

            if (
                $xRequestId !== null
                && trim($xRequestId) !== ''
            ) {
                $manifestParts[] =
                    'request-id:' . trim($xRequestId);
            }

            if (
                $timestamp !== null
                && trim($timestamp) !== ''
            ) {
                $manifestParts[] =
                    'ts:' . trim($timestamp);
            }

            $manifest = implode(
                ';',
                $manifestParts
            ) . ';';

            /*
             * Calculamos manualmente el HMAC-SHA256.
             */
            $computedHash = hash_hmac(
                'sha256',
                $manifest,
                $secret
            );

            /*
             * Comparamos la firma recibida contra
             * la firma que calculamos.
             *
             * No registramos ninguno de los dos hashes
             * originales; solamente su huella SHA-256.
             */
            $hashesMatch = (
                $receivedHash !== null
                && hash_equals(
                    $computedHash,
                    $receivedHash
                )
            );

            Log::channel('stderr')->warning(
                'MERCADO PAGO - DIAGNOSTICO HMAC',
                [
                    'request_id' => $xRequestId,
                    'data_id' => $dataId,
                    'timestamp' => $timestamp,

                    /*
                     * El manifest NO contiene secretos.
                     */
                    'manifest' => $manifest,

                    /*
                     * Huella del v1 enviado por Mercado Pago.
                     */
                    'received_hash_fingerprint' =>
                        $receivedHash
                            ? substr(
                                hash(
                                    'sha256',
                                    $receivedHash
                                ),
                                0,
                                16
                            )
                            : null,

                    /*
                     * Huella de nuestra firma calculada.
                     */
                    'computed_hash_fingerprint' =>
                        substr(
                            hash(
                                'sha256',
                                $computedHash
                            ),
                            0,
                            16
                        ),

                    /*
                     * Resultado directo de la comparación.
                     */
                    'hashes_match' => $hashesMatch,
                ]
            );

            /*
             * Mantener el comportamiento correcto:
             * una firma inválida debe producir 401.
             */
            Log::channel('stderr')->warning(
                'MERCADO PAGO - FIRMA INVALIDA',
                [
                    'request_id' => $xRequestId,
                    'data_id' => $dataId,
                    'timestamp' => $timestamp,
                ]
            );

            return false;

        } catch (\Throwable $e) {

            Log::channel('stderr')->error(
                'MERCADO PAGO - ERROR VALIDANDO FIRMA',
                [
                    'request_id' => $xRequestId,
                    'data_id' => $dataId,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );

            return false;
        }
    }
}