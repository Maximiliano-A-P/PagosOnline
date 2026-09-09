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
         *
         * Mercado Pago puede enviar el tipo de evento
         * en el body o como query parameter.
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
         * Mercado Pago puede enviar el payment_id
         * dentro del body o como query parameter.
         */
        $paymentId =
            $request->input('data.id')
            ?? $request->query('data.id')
            ?? $request->query('data_id');

        if (!$paymentId) {
            return response()->json([
                'message' => 'Payment ID no recibido.',
            ], 400);
        }

        /*
         * Headers utilizados para validar
         * la firma del Webhook.
         */
        $xSignature =
            $request->header('x-signature');

        $xRequestId =
            $request->header('x-request-id');

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
         * Validamos la firma del Webhook.
         */
        if (
            !$mercadoPagoService->validateWebhookSignature(
                $xSignature,
                $xRequestId,
                (string) $paymentId
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
         * El SDK de Mercado Pago expone estos campos
         * utilizando nombres snake_case.
         */
        $externalReference =
            $payment->external_reference
            ?? null;

        if (!$externalReference) {
            return response()->json([
                'message' =>
                    'La referencia externa no existe.',
            ], 400);
        }

        /*
         * La referencia externa debe contener
         * un ID numérico correspondiente a una factura.
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
         *
         * Esto hace que el Webhook sea idempotente.
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
         * El SDK expone transaction_amount
         * utilizando snake_case.
         */
        $transactionAmount =
            (float) (
                $payment->transaction_amount
                ?? 0
            );

        $invoiceAmount =
            (float) $invoice->price;

        /*
         * Verificamos que el importe recibido coincida
         * con el importe de nuestra factura.
         */
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
         * El SDK expone date_approved utilizando
         * snake_case.
         */
        $dateApproved =
            $payment->date_approved
            ?? null;

        $paidAt = null;

        if (!empty($dateApproved)) {
            $paidAt =
                date(
                    'Y-m-d',
                    strtotime($dateApproved)
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