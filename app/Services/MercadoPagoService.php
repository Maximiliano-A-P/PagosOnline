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
                 * ======================================================
                 * FORMA DE LA DOCUMENTACIÓN
                 * ======================================================
                 *
                 * URL pública utilizada por Mercado Pago
                 * para enviar las notificaciones.
                 */
                /*
                'notification_url' => config(
                    'services.mercadopago.webhook_url'
                ),
                */

                /*
                 * ======================================================
                 * FORMA REAL DE DEBUG - 09/2026
                 * ======================================================
                 */
                'notification_url' =>
                    'https://pagosonline.onrender.com/mercadopago/webhook',

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
                    'status_code' =>
                        $e->getApiResponse()->getStatusCode(),

                    'response' =>
                        $e->getApiResponse()->getContent(),

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
     * PRIMERA OPCIÓN:
     * Validador oficial del SDK utilizando data.id.
     *
     * SEGUNDA OPCIÓN:
     * Variante experimental utilizando notification.id.
     *
     * TERCERA PRUEBA:
     * Repetimos manualmente la fórmula oficial usando
     * explícitamente el data_id recibido desde el query string.
     *
     * En todos los casos se utiliza:
     *
     * HMAC-SHA256(secret, manifest)
     */
    public function validateWebhookSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId,
        ?string $notificationId,
        ?string $queryDataId = null
    ): bool {
        $secret = config(
            'services.mercadopago.webhook_secret'
        );

        Log::channel('stderr')->info(
            'MERCADO PAGO - INICIO VALIDACION FIRMA',
            [
                'has_signature' =>
                    !empty($xSignature),

                'has_request_id' =>
                    !empty($xRequestId),

                'has_data_id' =>
                    !empty($dataId),

                'has_notification_id' =>
                    !empty($notificationId),

                'has_query_data_id' =>
                    !empty($queryDataId),

                'secret_configured' =>
                    !empty($secret),

                'secret_length' =>
                    $secret
                        ? strlen($secret)
                        : 0,

                'secret_fingerprint' =>
                    $secret
                        ? substr(
                            hash(
                                'sha256',
                                $secret
                            ),
                            0,
                            12
                        )
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
             * ------------------------------------------------------
             * OBTENER TS Y V1
             * ------------------------------------------------------
             */

            $parts = [];

            foreach (
                explode(',', (string) $xSignature)
                as $part
            ) {
                $pieces = explode('=', $part, 2);

                if (count($pieces) !== 2) {
                    continue;
                }

                $key = strtolower(
                    trim($pieces[0])
                );

                $value = trim(
                    $pieces[1]
                );

                if (
                    $key === ''
                    || $value === ''
                ) {
                    continue;
                }

                $parts[$key] = $value;
            }

            $timestamp = $parts['ts'] ?? null;
            $receivedHash = $parts['v1'] ?? null;

            if (
                !$timestamp
                || !$receivedHash
            ) {
                Log::channel('stderr')->warning(
                    'MERCADO PAGO - X-SIGNATURE INCOMPLETO'
                );

                return false;
            }

            /*
             * ======================================================
             * PRIMERA PRUEBA
             * FORMA OFICIAL DEL SDK
             * ======================================================
             *
             * id:<data.id>;
             * request-id:<x-request-id>;
             * ts:<timestamp>;
             */

            try {
                WebhookSignatureValidator::validate(
                    $xSignature,
                    $xRequestId,
                    $dataId,
                    $secret
                );

                Log::channel('stderr')->info(
                    'MERCADO PAGO - FIRMA VALIDA CON DATA.ID'
                );

                return true;

            } catch (InvalidWebhookSignatureException $e) {

                Log::channel('stderr')->warning(
                    'MERCADO PAGO - FIRMA NO COINCIDE CON DATA.ID',
                    [
                        'data_id' =>
                            $dataId,

                        'notification_id' =>
                            $notificationId,

                        'timestamp' =>
                            $timestamp,
                    ]
                );
            }

            /*
             * ======================================================
             * SEGUNDA PRUEBA
             * VARIANTE EXPERIMENTAL CON NOTIFICATION.ID
             * ======================================================
             */

            $manifestNotificationParts = [];

            if (
                $notificationId !== null
                && trim($notificationId) !== ''
            ) {
                $manifestNotificationParts[] =
                    'id:' . trim($notificationId);
            }

            if (
                $xRequestId !== null
                && trim($xRequestId) !== ''
            ) {
                $manifestNotificationParts[] =
                    'request-id:' . trim($xRequestId);
            }

            if (
                $timestamp !== null
                && trim($timestamp) !== ''
            ) {
                $manifestNotificationParts[] =
                    'ts:' . trim($timestamp);
            }

            $notificationManifest =
                implode(
                    ';',
                    $manifestNotificationParts
                ) . ';';

            $notificationHash = hash_hmac(
                'sha256',
                $notificationManifest,
                $secret
            );

            $notificationMatches =
                hash_equals(
                    $notificationHash,
                    $receivedHash
                );

            Log::channel('stderr')->warning(
                'MERCADO PAGO - PRUEBA FIRMA CON NOTIFICATION.ID',
                [
                    'notification_id' =>
                        $notificationId,

                    'data_id' =>
                        $dataId,

                    'timestamp' =>
                        $timestamp,

                    'manifest' =>
                        $notificationManifest,

                    'received_hash_fingerprint' =>
                        substr(
                            hash(
                                'sha256',
                                $receivedHash
                            ),
                            0,
                            16
                        ),

                    'computed_hash_fingerprint' =>
                        substr(
                            hash(
                                'sha256',
                                $notificationHash
                            ),
                            0,
                            16
                        ),

                    'hashes_match' =>
                        $notificationMatches,
                ]
            );

            if ($notificationMatches) {
                Log::channel('stderr')->warning(
                    'MERCADO PAGO - FIRMA VALIDA CON NOTIFICATION.ID',
                    [
                        'message' =>
                            'Se utilizó la forma alternativa '
                            . 'de validación 09/2026.',
                    ]
                );

                return true;
            }

            /*
             * ======================================================
             * TERCERA PRUEBA
             * DATA.ID TOMADO EXPLICITAMENTE DEL QUERY STRING
             * ======================================================
             *
             * PHP/Laravel puede representar:
             *
             * ?data.id=123
             *
             * como:
             *
             * data_id=123
             *
             * en los parámetros de la petición.
             *
             * Por eso repetimos manualmente la misma fórmula
             * oficial, pero utilizando exclusivamente
             * el valor obtenido del query string.
             */

            $queryDataId =
                $queryDataId !== null
                ? trim($queryDataId)
                : null;

            $manualParts = [];

            if (
                $queryDataId !== null
                && $queryDataId !== ''
            ) {
                $manualParts[] =
                    'id:' . $queryDataId;
            }

            if (
                $xRequestId !== null
                && trim($xRequestId) !== ''
            ) {
                $manualParts[] =
                    'request-id:' . trim($xRequestId);
            }

            if (
                $timestamp !== null
                && trim($timestamp) !== ''
            ) {
                $manualParts[] =
                    'ts:' . trim($timestamp);
            }

            $manualManifest =
                implode(
                    ';',
                    $manualParts
                ) . ';';

            $manualHash = hash_hmac(
                'sha256',
                $manualManifest,
                $secret
            );

            $manualMatches =
                hash_equals(
                    $manualHash,
                    $receivedHash
                );

            Log::channel('stderr')->warning(
                'MERCADO PAGO - TERCERA PRUEBA DATA.ID QUERY',
                [
                    'query_data_id' =>
                        $queryDataId,

                    'data_id_normalizado' =>
                        $dataId,

                    'notification_id' =>
                        $notificationId,

                    'timestamp' =>
                        $timestamp,

                    'manifest' =>
                        $manualManifest,

                    'received_hash_fingerprint' =>
                        substr(
                            hash(
                                'sha256',
                                $receivedHash
                            ),
                            0,
                            16
                        ),

                    'computed_hash_fingerprint' =>
                        substr(
                            hash(
                                'sha256',
                                $manualHash
                            ),
                            0,
                            16
                        ),

                    'hashes_match' =>
                        $manualMatches,
                ]
            );

            if ($manualMatches) {
                Log::channel('stderr')->warning(
                    'MERCADO PAGO - FIRMA VALIDA CON DATA.ID QUERY',
                    [
                        'message' =>
                            'La fórmula oficial coincide '
                            . 'al utilizar directamente el '
                            . 'data_id del query string.',
                    ]
                );

                return true;
            }

            /*
             * Ninguna de las pruebas coincidió.
             */
            Log::channel('stderr')->warning(
                'MERCADO PAGO - FIRMA INVALIDA EN TODAS LAS PRUEBAS',
                [
                    'data_id' =>
                        $dataId,

                    'query_data_id' =>
                        $queryDataId,

                    'notification_id' =>
                        $notificationId,

                    'timestamp' =>
                        $timestamp,
                ]
            );

            return false;

        } catch (\Throwable $e) {

            Log::channel('stderr')->error(
                'MERCADO PAGO - ERROR VALIDANDO FIRMA',
                [
                    'data_id' =>
                        $dataId,

                    'query_data_id' =>
                        $queryDataId,

                    'notification_id' =>
                        $notificationId,

                    'message' =>
                        $e->getMessage(),

                    'exception' =>
                        get_class($e),
                ]
            );

            return false;
        }
    }
}