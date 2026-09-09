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
         * Obtenemos el payment_id.
         *
         * Mercado Pago puede enviarlo:
         * - en el body como data.id
         * - en el query string como data.id
         * - en el query string como data_id
         */
        $queryDataId =
            $request->query('data.id')
            ?? $request->query('data_id');

        $bodyDataId =
            $request->input('data.id');

        $paymentId =
            $bodyDataId
            ?? $queryDataId;

        if (!$paymentId) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - PAYMENT ID NO RECIBIDO',
                [
                    'query' => $request->query(),
                    'body' => $request->all(),
                ]
            );

            return response()->json([
                'message' =>
                    'Payment ID no recibido.',
            ], 400);
        }

        /*
         * ID de la notificación.
         */
        $notificationId =
            $request->input('id');

        /*
         * Headers de seguridad.
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

        /*
         * Mercado Pago debe enviar ambos headers.
         */
        if (
            !$xSignature
            || !$xRequestId
        ) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - FIRMA O REQUEST ID AUSENTE',
                [
                    'has_signature' =>
                        !empty($xSignature),

                    'has_request_id' =>
                        !empty($xRequestId),

                    'payment_id' =>
                        (string) $paymentId,
                ]
            );

            return response()->json([
                'message' =>
                    'Firma de Webhook no recibida.',
            ], 401);
        }

        /*
         * Validamos la firma.
         *
         * Actualmente la firma válida se obtiene
         * con la forma oficial usando data.id.
         */
        if (
            !$mercadoPagoService->validateWebhookSignature(
                $xSignature,
                $xRequestId,
                (string) $paymentId,
                $notificationId !== null
                    ? (string) $notificationId
                    : null,
                $queryDataId !== null
                    ? (string) $queryDataId
                    : null
            )
        ) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - WEBHOOK RECHAZADO POR FIRMA',
                [
                    'payment_id' =>
                        (string) $paymentId,

                    'notification_id' =>
                        $notificationId,

                    'request_id' =>
                        $xRequestId,
                ]
            );

            return response()->json([
                'message' =>
                    'Firma de Webhook inválida.',
            ], 401);
        }

        /*
         * ==========================================================
         * CONSULTAR PAYMENT EN MERCADO PAGO
         * ==========================================================
         */
        try {
            $payment =
                $mercadoPagoService->getPayment(
                    (string) $paymentId
                );

        } catch (\Throwable $e) {

            Log::channel('stderr')->error(
                'MERCADO PAGO - ERROR CONSULTANDO PAYMENT',
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
         * Verificamos que exista el Payment.
         */
        if (!$payment) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - PAYMENT VACIO',
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
         * Guardamos algunos datos del Payment en logs
         * para diagnosticar el siguiente error.
         *
         * No registramos datos sensibles.
         */
        Log::channel('stderr')->info(
            'MERCADO PAGO - PAYMENT CONSULTADO',
            [
                'payment_id' =>
                    isset($payment->id)
                        ? (string) $payment->id
                        : null,

                'status' =>
                    $payment->status ?? null,

                'external_reference' =>
                    $payment->externalReference ?? null,

                'transaction_amount' =>
                    $payment->transactionAmount ?? null,

                'date_approved' =>
                    $payment->dateApproved ?? null,
            ]
        );

        /*
         * Verificamos que el ID devuelto por Mercado Pago
         * coincida con el recibido.
         */
        if (
            isset($payment->id)
            && (string) $payment->id
                !== (string) $paymentId
        ) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - PAYMENT ID NO COINCIDE',
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
         * ==========================================================
         * EXTERNAL REFERENCE
         * ==========================================================
         */
        $externalReference =
            $payment->externalReference
            ?? null;

        if (!$externalReference) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - EXTERNAL REFERENCE AUSENTE',
                [
                    'payment_id' =>
                        (string) $paymentId,

                    'payment_status' =>
                        $payment->status ?? null,
                ]
            );

            return response()->json([
                'message' =>
                    'La referencia externa no existe.',
            ], 400);
        }

        /*
         * La referencia externa debe ser numérica.
         */
        if (
            !ctype_digit(
                (string) $externalReference
            )
        ) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - EXTERNAL REFERENCE INVALIDA',
                [
                    'payment_id' =>
                        (string) $paymentId,

                    'external_reference' =>
                        (string) $externalReference,
                ]
            );

            return response()->json([
                'message' =>
                    'La referencia externa no es válida.',
            ], 400);
        }

        /*
         * ==========================================================
         * BUSCAR FACTURA
         * ==========================================================
         */
        $invoice =
            Invoice::find(
                (int) $externalReference
            );

        if (!$invoice) {
            Log::channel('stderr')->error(
                'MERCADO PAGO - FACTURA NO ENCONTRADA',
                [
                    'payment_id' =>
                        (string) $paymentId,

                    'external_reference' =>
                        (string) $externalReference,
                ]
            );

            return response()->json([
                'message' =>
                    'Factura no encontrada.',
            ], 404);
        }

        /*
         * ==========================================================
         * IDEMPOTENCIA
         * ==========================================================
         */
        if ($invoice->payment_status === 'paid') {
            Log::channel('stderr')->info(
                'MERCADO PAGO - FACTURA YA PAGADA',
                [
                    'invoice_id' =>
                        $invoice->id,

                    'payment_id' =>
                        (string) $paymentId,
                ]
            );

            return response()->json([
                'message' =>
                    'La factura ya estaba pagada.',
            ]);
        }

        /*
         * ==========================================================
         * ESTADO DEL PAYMENT
         * ==========================================================
         */
        if (
            ($payment->status ?? null)
            !== 'approved'
        ) {
            Log::channel('stderr')->warning(
                'MERCADO PAGO - PAYMENT NO APROBADO',
                [
                    'invoice_id' =>
                        $invoice->id,

                    'payment_id' =>
                        (string) $paymentId,

                    'status' =>
                        $payment->status ?? null,
                ]
            );

            return response()->json([
                'message' =>
                    'El pago todavía no está aprobado.',
            ]);
        }

        /*
         * ==========================================================
         * IMPORTE
         * ==========================================================
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
            Log::channel('stderr')->error(
                'MERCADO PAGO - IMPORTE NO COINCIDE',
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
         * ==========================================================
         * FECHA DE PAGO
         * ==========================================================
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

        if (!$paidAt) {
            $paidAt =
                now()->toDateString();
        }

        /*
         * ==========================================================
         * ACTUALIZAR FACTURA
         * ==========================================================
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

        Log::channel('stderr')->info(
            'MERCADO PAGO - FACTURA MARCADA COMO PAGADA',
            [
                'invoice_id' =>
                    $invoice->id,

                'payment_id' =>
                    (string) $paymentId,

                'amount_paid' =>
                    $transactionAmount,

                'paid_at' =>
                    $paidAt,
            ]
        );

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