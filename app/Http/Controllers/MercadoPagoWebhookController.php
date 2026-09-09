<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Procesa las notificaciones de Mercado Pago.
     */
    public function handle(
        Request $request,
        MercadoPagoService $mercadoPagoService
    ): JsonResponse {
        /*
         * Solo procesamos notificaciones relacionadas
         * con pagos.
         */
        $type =
            $request->input('type')
            ?? $request->query('type');

        if ($type !== 'payment') {
            return response()->json([
                'message' => 'Evento ignorado.',
            ]);
        }

        /*
         * Mercado Pago envía el payment_id mediante:
         *
         * ?data.id=...
         *
         * PHP/Laravel puede exponer ese parámetro como:
         *
         * data_id
         *
         * Por eso se contemplan ambas formas.
         */
        $queryDataId =
            $request->query('data.id')
            ?? $request->query('data_id');

        /*
         * También contemplamos un data.id presente en el body.
         */
        $bodyDataId =
            $request->input('data.id');

        $paymentId =
            $bodyDataId
            ?? $queryDataId;

        if (!$paymentId) {
            return response()->json([
                'message' =>
                    'Payment ID no recibido.',
            ], 400);
        }

        /*
         * ==========================================================
         * ID DE LA NOTIFICACIÓN
         * ==========================================================
         *
         * body.id     -> ID único de la notificación
         * data.id     -> ID del recurso notificado
         */
        $notificationId =
            $request->input('id');

        /*
         * Validamos el origen de la notificación.
         */
        $xSignature =
            $request->header('x-signature');

        $xRequestId =
            $request->header('x-request-id');

        Log::channel('stderr')->info(
            'MERCADO PAGO WEBHOOK FIRMA',
            [
                'has_signature' =>
                    !empty($xSignature),

                'has_request_id' =>
                    !empty($xRequestId),

                'request_id' =>
                    $xRequestId,

                'data_id' =>
                    $paymentId,

                'query_data_id' =>
                    $queryDataId,

                'body_data_id' =>
                    $bodyDataId,

                'notification_id' =>
                    $notificationId,

                'webhook_secret_configured' =>
                    !empty(
                        config(
                            'services.mercadopago.webhook_secret'
                        )
                    ),

                'signature' =>
                    $xSignature
                        ? preg_replace(
                            '/(ts|v1)=[^,]+/',
                            '$1=***',
                            $xSignature
                        )
                        : null,
            ]
        );

        if (
            !$xSignature
            || !$xRequestId
        ) {
            return response()->json([
                'message' =>
                    'Firma de Webhook no recibida.',
            ], 401);
        }

        /*
         * ==========================================================
         * VALIDACIÓN DE FIRMA
         * ==========================================================
         *
         * 1. Validador oficial con data.id.
         * 2. Variante experimental con notification.id.
         * 3. Reproducción manual usando query data_id.
         */
        if (
            !$mercadoPagoService->validateWebhookSignature(
                $xSignature,
                $xRequestId,
                $paymentId !== null
                    ? (string) $paymentId
                    : null,
                $notificationId !== null
                    ? (string) $notificationId
                    : null,
                $queryDataId !== null
                    ? (string) $queryDataId
                    : null
            )
        ) {
            return response()->json([
                'message' =>
                    'Firma de Webhook inválida.',
            ], 401);
        }

        /*
         * Consultamos el pago directamente a Mercado Pago.
         *
         * No confiamos únicamente en los datos recibidos
         * mediante el Webhook.
         */
        try {
            $payment =
                $mercadoPagoService->getPayment(
                    (string) $paymentId
                );

        } catch (\Throwable $e) {

            Log::error(
                'No se pudo consultar el pago de Mercado Pago.',
                [
                    'payment_id' =>
                        (string) $paymentId,

                    'message' =>
                        $e->getMessage(),

                    'exception' =>
                        get_class($e),
                ]
            );

            return response()->json([
                'message' =>
                    'No se pudo consultar el pago.',
            ], 500);
        }

        /*
         * Verificamos que Mercado Pago haya devuelto
         * efectivamente un pago.
         */
        if (!$payment) {
            Log::error(
                'Mercado Pago no devolvió información del pago.',
                [
                    'payment_id' =>
                        (string) $paymentId,
                ]
            );

            return response()->json([
                'message' =>
                    'No se pudo obtener el pago.',
            ], 500);
        }

        /*
         * Verificamos que el ID devuelto por Mercado Pago
         * coincida con el ID recibido en el Webhook.
         */
        if (
            isset($payment->id)
            && (string) $payment->id
                !== (string) $paymentId
        ) {
            Log::warning(
                'El payment_id recibido no coincide con el pago consultado.',
                [
                    'webhook_payment_id' =>
                        (string) $paymentId,

                    'api_payment_id' =>
                        (string) $payment->id,
                ]
            );

            return response()->json([
                'message' =>
                    'El pago recibido no coincide con el pago consultado.',
            ], 400);
        }

        /*
         * Obtenemos la referencia externa que corresponde
         * a nuestra factura.
         */
        $externalReference =
            $payment->externalReference
            ?? null;

        if (!$externalReference) {
            return response()->json([
                'message' =>
                    'La referencia externa no existe.',
            ], 400);
        }

        /*
         * La referencia externa debe contener un ID numérico.
         */
        if (
            !ctype_digit(
                (string) $externalReference
            )
        ) {
            return response()->json([
                'message' =>
                    'La referencia externa no es válida.',
            ], 400);
        }

        /*
         * Buscamos la factura correspondiente.
         */
        $invoice =
            Invoice::find(
                (int) $externalReference
            );

        if (!$invoice) {
            return response()->json([
                'message' =>
                    'Factura no encontrada.',
            ], 404);
        }

        /*
         * Si la factura ya fue pagada,
         * no volvemos a procesarla.
         */
        if ($invoice->payment_status === 'paid') {
            return response()->json([
                'message' =>
                    'La factura ya estaba pagada.',
            ]);
        }

        /*
         * Solo un pago aprobado puede marcar
         * la factura como pagada.
         */
        if (
            ($payment->status ?? null)
            !== 'approved'
        ) {
            return response()->json([
                'message' =>
                    'El pago todavía no está aprobado.',
            ]);
        }

        /*
         * Verificamos que el importe recibido coincida
         * con el importe de nuestra factura.
         */
        $transactionAmount =
            (float) (
                $payment->transactionAmount
                ?? 0
            );

        $invoiceAmount =
            (float) $invoice->price;

        if (
            abs(
                $transactionAmount
                - $invoiceAmount
            ) > 0.01
        ) {
            Log::warning(
                'El importe del pago no coincide con la factura.',
                [
                    'invoice_id' =>
                        $invoice->id,

                    'payment_id' =>
                        (string) $paymentId,

                    'invoice_amount' =>
                        $invoiceAmount,

                    'transaction_amount' =>
                        $transactionAmount,
                ]
            );

            return response()->json([
                'message' =>
                    'El importe del pago no coincide con la factura.',
            ], 400);
        }

        /*
         * Obtenemos la fecha de aprobación del pago.
         */
        $paidAt = null;

        if (!empty($payment->dateApproved)) {
            $paidAt =
                date(
                    'Y-m-d',
                    strtotime(
                        $payment->dateApproved
                    )
                );
        }

        /*
         * Si Mercado Pago no proporciona una fecha
         * válida, utilizamos la fecha actual.
         */
        if (!$paidAt) {
            $paidAt =
                now()->toDateString();
        }

        /*
         * Guardamos el resultado confirmado del pago.
         */
        $invoice->update([
            'payment_status' =>
                'paid',

            'amount_paid' =>
                $transactionAmount,

            'paid_at' =>
                $paidAt,

            'payment_method' =>
                'mercadopago',

            'paid_by' =>
                null,

            'mercadopago_payment_id' =>
                (string) $paymentId,
        ]);

        /*
         * Mercado Pago considera recibida correctamente
         * la notificación cuando devolvemos HTTP 200.
         */
        return response()->json([
            'message' =>
                'Pago procesado correctamente.',
        ], 200);
    }
}