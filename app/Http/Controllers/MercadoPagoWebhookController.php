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
         * Validamos el origen de la notificación.
         */
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');

        Log::channel('stderr')->info(
            'MERCADO PAGO WEBHOOK FIRMA',
            [
                'has_signature' => !empty($xSignature),
                'has_request_id' => !empty($xRequestId),
                'request_id' => $xRequestId,
                'data_id' => $paymentId,
                'webhook_secret_configured' => !empty(
                    config('services.mercadopago.webhook_secret')
                ),
                'signature' => $xSignature
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
                'message' => 'Firma de Webhook no recibida.',
            ], 401);
        }

        if (
            !$mercadoPagoService->validateWebhookSignature(
                $xSignature,
                $xRequestId,
                (string) $paymentId
            )
        ) {
            return response()->json([
                'message' => 'Firma de Webhook inválida.',
            ], 401);
        }

        /*
         * Consultamos el pago directamente a Mercado Pago.
         *
         * No confiamos únicamente en los datos recibidos
         * mediante el Webhook.
         */
        try {
            $payment = $mercadoPagoService->getPayment(
                (string) $paymentId
            );
        } catch (\Throwable $e) {

            /*
             * Si Mercado Pago no puede ser consultado
             * temporalmente, devolvemos 500 para que la
             * notificación pueda ser reenviada.
             */
            Log::error(
                'No se pudo consultar el pago de Mercado Pago.',
                [
                    'payment_id' => (string) $paymentId,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );

            return response()->json([
                'message' => 'No se pudo consultar el pago.',
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
                    'payment_id' => (string) $paymentId,
                ]
            );

            return response()->json([
                'message' => 'No se pudo obtener el pago.',
            ], 500);
        }

        /*
         * Verificamos que el ID devuelto por Mercado Pago
         * coincida con el ID recibido en el Webhook.
         */
        if (
            isset($payment->id)
            && (string) $payment->id !== (string) $paymentId
        ) {
            Log::warning(
                'El payment_id recibido no coincide con el pago consultado.',
                [
                    'webhook_payment_id' => (string) $paymentId,
                    'api_payment_id' => (string) $payment->id,
                ]
            );

            return response()->json([
                'message' => 'El pago recibido no coincide con el pago consultado.',
            ], 400);
        }

        /*
         * Obtenemos la referencia externa que corresponde
         * a nuestra factura.
         *
         * Al crear la Preference utilizamos el ID de la
         * factura como external_reference.
         */
        $externalReference =
            $payment->externalReference
            ?? null;

        if (!$externalReference) {
            return response()->json([
                'message' => 'La referencia externa no existe.',
            ], 400);
        }

        /*
         * La referencia externa debe contener un ID numérico
         * correspondiente a una factura de nuestro sistema.
         */
        if (
            !ctype_digit(
                (string) $externalReference
            )
        ) {
            return response()->json([
                'message' => 'La referencia externa no es válida.',
            ], 400);
        }

        /*
         * Buscamos la factura correspondiente.
         */
        $invoice = Invoice::find(
            (int) $externalReference
        );

        if (!$invoice) {
            return response()->json([
                'message' => 'Factura no encontrada.',
            ], 404);
        }

        /*
         * Si la factura ya fue pagada, no volvemos a procesarla.
         *
         * Esto hace que el Webhook sea idempotente.
         */
        if ($invoice->payment_status === 'paid') {
            return response()->json([
                'message' => 'La factura ya estaba pagada.',
            ]);
        }

        /*
         * Solo un pago aprobado puede marcar la factura
         * como pagada.
         */
        if (
            ($payment->status ?? null)
            !== 'approved'
        ) {
            return response()->json([
                'message' => 'El pago todavía no está aprobado.',
            ]);
        }

        /*
         * Verificamos que el importe recibido coincida
         * con el importe de nuestra factura.
         */
        $transactionAmount = (float) (
            $payment->transactionAmount ?? 0
        );

        $invoiceAmount = (float) $invoice->price;

        if (
            abs(
                $transactionAmount - $invoiceAmount
            ) > 0.01
        ) {
            Log::warning(
                'El importe del pago no coincide con la factura.',
                [
                    'invoice_id' => $invoice->id,
                    'payment_id' => (string) $paymentId,
                    'invoice_amount' => $invoiceAmount,
                    'transaction_amount' => $transactionAmount,
                ]
            );

            return response()->json([
                'message' => 'El importe del pago no coincide con la factura.',
            ], 400);
        }

        /*
         * Obtenemos la fecha de aprobación del pago.
         *
         * La columna paid_at actualmente guarda solamente
         * la fecha.
         */
        $paidAt = null;

        if (!empty($payment->dateApproved)) {
            $paidAt = date(
                'Y-m-d',
                strtotime($payment->dateApproved)
            );
        }

        /*
         * Si Mercado Pago no proporciona una fecha de
         * aprobación válida, utilizamos la fecha actual.
         */
        if (!$paidAt) {
            $paidAt = now()->toDateString();
        }

        /*
         * Guardamos el resultado confirmado del pago.
         */
        $invoice->update([
            'payment_status' => 'paid',
            'amount_paid' => $transactionAmount,
            'paid_at' => $paidAt,
            'payment_method' => 'mercadopago',
            'paid_by' => null,
            'mercadopago_payment_id' => (string) $paymentId,
        ]);

        /*
         * Mercado Pago considera recibida correctamente
         * la notificación cuando devolvemos HTTP 200.
         */
        return response()->json([
            'message' => 'Pago procesado correctamente.',
        ], 200);
    }
}