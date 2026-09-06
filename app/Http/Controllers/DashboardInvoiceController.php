<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\MercadoPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardInvoiceController extends Controller
{
    /**
     * Inicia el pago online de una factura.
     */
    public function pay(
        Invoice $invoice,
        MercadoPagoService $mercadoPagoService
    ): RedirectResponse {
        /*
         * Obtenemos los documentos de los clientes asociados
         * al usuario actual mediante la relación de Eloquent.
         */
        $clientDocuments = Auth::user()
            ->clients()
            ->pluck('document');

        /*
         * Convertimos todos los documentos a string
         * para realizar una comparación estricta.
         */
        $documents = $clientDocuments
            ->map('strval')
            ->toArray();

        /*
         * Una factura solamente puede ser pagada
         * si pertenece a uno de los clientes asociados
         * al usuario actual.
         */
        if (
            !in_array(
                (string) $invoice->client_document,
                $documents,
                true
            )
        ) {
            abort(403);
        }

        /*
         * No permitimos iniciar un pago para una factura
         * que ya figura como pagada.
         */
        if ($invoice->payment_status === 'paid') {
            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'La factura ya figura como pagada.'
                );
        }

        /*
         * Creamos la preferencia de pago en Mercado Pago.
         *
         * El servicio se encarga de comunicarse con la API.
         */
        try {
            $preference = $mercadoPagoService->createPreference(
                $invoice
            );
        } catch (\Throwable $e) {

            /*
             * El servicio ya registra el error de Mercado Pago.
             *
             * Aquí registramos además el intento asociado
             * con esta factura.
             */
            Log::error(
                'No se pudo iniciar el pago de la factura.',
                [
                    'invoice_id' => $invoice->id,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );

            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'No se pudo iniciar el pago. Intente nuevamente.'
                );
        }

        /*
         * Verificamos que Mercado Pago haya devuelto
         * los datos necesarios para continuar.
         */
        if (
            empty($preference->id)
            || empty($preference->init_point)
        ) {
            Log::error(
                'Mercado Pago devolvió una Preference incompleta.',
                [
                    'invoice_id' => $invoice->id,
                    'preference_id' => $preference->id ?? null,
                    'init_point' => $preference->init_point ?? null,
                ]
            );

            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'No se pudo iniciar el pago. Intente nuevamente.'
                );
        }

        /*
         * Guardamos el ID de la preferencia.
         *
         * Esto nos permite saber qué Preference fue creada
         * para esta factura.
         */
        $invoice->update([
            'mercadopago_preference_id' => $preference->id,
        ]);

        /*
         * Redirigimos al usuario a Mercado Pago Checkout Pro.
         */
        return redirect()->away(
            $preference->init_point
        );
    }

    /**
     * Muestra el historial de facturas de los clientes del usuario.
     */
    public function history()
    {
        $user = Auth::user();

        /*
         * Obtenemos los documentos de los clientes asociados
         * al usuario actual.
         */
        $clientDocuments = $user
            ->clients()
            ->pluck('document');

        /*
         * Buscamos todas las facturas correspondientes
         * a esos documentos.
         */
        $invoices = $clientDocuments->isEmpty()
            ? collect()
            : Invoice::whereIn(
                'client_document',
                $clientDocuments
            )
                ->orderBy(
                    'issued_at',
                    'desc'
                )
                ->paginate(10);

        return view(
            'dashboard.invoices.history',
            compact('invoices')
        );
    }
}