<?php

namespace App\Services;

use App\Models\ArcaConfig;
use App\Models\Invoice;
use App\Services\Arca\ArcaApiService;
use App\Services\Arca\WsaaClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Simula en local el resultado de un pago aprobado de Mercado Pago.
 *
 * En local Mercado Pago no puede llamar al webhook ni redirigir de
 * vuelta (localhost no es una URL pública), así que al tocar "Pagar"
 * se confirma el pago directamente, con los mismos efectos que tiene
 * el webhook real: factura pagada + emisión ante ARCA + correo.
 *
 * Solo funciona con APP_ENV=local y MERCADOPAGO_SIMULATE=true.
 */
class MercadoPagoSimulator
{
    /**
     * True si la simulación está activa.
     *
     * Exige APP_ENV=local para que nunca se active por error en Render.
     */
    public function activo(): bool
    {
        return (bool) config('services.mercadopago.simulate')
            && app()->environment('local');
    }

    /**
     * Confirma el pago de la factura como si Mercado Pago lo hubiera
     * aprobado.
     *
     * Devuelve false si la factura ya estaba pagada (idempotente).
     */
    public function confirmarPago(Invoice $invoice): bool
    {
        /*
         * Fecha de hoy en horario argentino (el servidor trabaja
         * en UTC). Se usa para decidir si corresponde el valor
         * normal o el vencido.
         */
        $hoy = Carbon::createFromFormat(
            'Y-m-d',
            now('America/Argentina/Buenos_Aires')->toDateString()
        )->startOfDay();

        $confirmado = false;

        /*
         * La factura se bloquea (lockForUpdate) mientras se la marca
         * como pagada: si el usuario toca "Pagar" dos veces seguidas,
         * solo una de las dos solicitudes la confirma y emite ante ARCA.
         */
        DB::transaction(function () use ($invoice, $hoy, &$confirmado) {

            $bloqueada = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->first();

            if (! $bloqueada || $bloqueada->payment_status === 'paid') {
                return;
            }

            $total = $bloqueada->montoAl($hoy);

            $bloqueada->update([
                'expected_amount' => $total,
                'payment_status' => 'paid',
                'amount_paid' => $total,
                'paid_at' => $hoy->toDateString(),
                'payment_method' => 'mercadopago',
                'paid_by' => null,
                'mercadopago_payment_id' => 'LOCAL-' . $bloqueada->id . '-' . time(),
            ]);

            $confirmado = true;
        });

        if (! $confirmado) {
            return false;
        }

        /*
         * Emisión ante ARCA, igual que en el webhook: un error acá
         * no deshace el pago, queda registrado en arca_status para
         * reintentar desde el admin.
         */
        try {
            $arcaConfig = ArcaConfig::firstOrFail();

            $wsaaClient = new WsaaClient($arcaConfig);

            (new ArcaApiService($wsaaClient, $arcaConfig))
                ->emitir($invoice->fresh(), false);

        } catch (\Throwable $e) {

            Log::error(
                'No se pudo emitir la factura ante ARCA (pago simulado).',
                [
                    'invoice_id' => $invoice->id,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );
        }

        return true;
    }
}
