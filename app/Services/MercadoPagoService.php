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
                 *
                 * Se mantiene comentada durante el diagnóstico.
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
                 *
                 * Durante el diagnóstico enviamos las notificaciones
                 * directamente al endpoint de debug para poder observar
                 * exactamente qué está enviando Mercado Pago.
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
     * Utiliza el validador oficial incluido en el SDK,
     * utilizando data.id.
     *
     * SEGUNDA OPCIÓN / FALLBACK EXPERIMENTAL:
     * Si la forma oficial falla, se prueba utilizando
     * el id de la notificación presente en el body.
     *
     * En ambos casos:
     *
     * HMAC-SHA256(secret, manifest)
     *
     * El secret nunca se registra.
     */
    public function validateWebhookSignature(
        ?string $xSignature,
        ?string $xRequestId,
        ?string $dataId,
        ?string $notificationId
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
                'has_notification_id' => !empty($notificationId),

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
                    ? substr(
                        hash('sha256', $secret),
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
             * FORMA DE LA DOCUMENTACIÓN - 09/2026
             * ======================================================
             *
             * El SDK oficial utiliza:
             *
             * id:<data.id>;
             * request-id:<x-request-id>;
             * ts:<timestamp>;
             *
             * Primero intentamos exactamente esta implementación.
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

                /*
                 * La forma oficial falló.
                 *
                 * Continuamos con la segunda variante experimental.
                 */

                Log::channel('stderr')->warning(
                    'MERCADO PAGO - FIRMA NO COINCIDE CON DATA.ID',
                    [
                        'data_id' => $dataId,
                        'notification_id' => $notificationId,
                        'timestamp' => $timestamp,
                    ]
                );
            }

            /*
             * ======================================================
             * FORMA ALTERNATIVA / FALLBACK - 09/2026
             * ======================================================
             *
             * Probamos:
             *
             * id:<notification.id>;
             * request-id:<x-request-id>;
             * ts:<timestamp>;
             *
             * Esta variante se prueba únicamente si la forma
             * documentada con data.id no coincide.
             */

            $manifestParts = [];

            if (
                $notificationId !== null
                && trim($notificationId) !== ''
            ) {
                $manifestParts[] =
                    'id:' . trim($notificationId);
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
             * Calculamos HMAC-SHA256 utilizando
             * exactamente el mismo secret.
             */
            $computedHash = hash_hmac(
                'sha256',
                $manifest,
                $secret
            );

            /*
             * Comparamos contra el v1 recibido.
             */
            $hashesMatch =
                hash_equals(
                    $computedHash,
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

                    /*
                     * El manifest no contiene secretos.
                     */
                    'manifest' =>
                        $manifest,

                    /*
                     * Huella del v1 recibido.
                     */
                    'received_hash_fingerprint' =>
                        substr(
                            hash(
                                'sha256',
                                $receivedHash
                            ),
                            0,
                            16
                        ),

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

                    'hashes_match' =>
                        $hashesMatch,
                ]
            );

            if ($hashesMatch) {
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
             * Ninguna de las dos formas coincidió.
             */
            Log::channel('stderr')->warning(
                'MERCADO PAGO - FIRMA INVALIDA EN AMBAS FORMAS',
                [
                    'data_id' => $dataId,
                    'notification_id' => $notificationId,
                    'timestamp' => $timestamp,
                ]
            );

            return false;

        } catch (\Throwable $e) {

            Log::channel('stderr')->error(
                'MERCADO PAGO - ERROR VALIDANDO FIRMA',
                [
                    'data_id' => $dataId,
                    'notification_id' => $notificationId,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );

            return false;
        }
    }
}