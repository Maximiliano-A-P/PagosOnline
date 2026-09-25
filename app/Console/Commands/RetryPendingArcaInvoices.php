<?php

namespace App\Console\Commands;

use App\Models\ArcaConfig;
use App\Models\Invoice;
use App\Services\Arca\ArcaApiService;
use App\Services\Arca\WsaaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryPendingArcaInvoices extends Command
{
    protected $signature =
        'arca:retry-pending';

    protected $description =
        'Reintenta automáticamente facturas pagadas pendientes de ARCA';

    public function handle(): int
    {
        $config =
            ArcaConfig::first();

        if (!$config) {
            $this->warn(
                'No existe configuración de ARCA.'
            );

            return self::SUCCESS;
        }

        $limite =
            max(
                1,
                (int) config(
                    'arca.retry.max_per_run',
                    1
                )
            );

        $maxIntentos =
            max(
                1,
                (int) config(
                    'arca.retry.max_attempts',
                    8
                )
            );

        $invoices =
            Invoice::query()
                ->where(
                    'payment_status',
                    'paid'
                )
                ->whereNull(
                    'arca_cae'
                )
                ->whereNotNull(
                    'arca_retry_at'
                )
                ->where(
                    'arca_retry_at',
                    '<=',
                    now()
                )
                ->where(
                    'arca_retry_attempts',
                    '<',
                    $maxIntentos
                )
                ->orderBy(
                    'issued_at'
                )
                ->orderBy(
                    'id'
                )
                ->limit($limite)
                ->get();

        if (
            $invoices->isEmpty()
        ) {
            $this->info(
                'No hay facturas pendientes de reintento ARCA.'
            );

            return self::SUCCESS;
        }

        $service =
            new ArcaApiService(
                new WsaaClient($config),
                $config
            );

        foreach ($invoices as $invoice) {

            try {

                $this->info(
                    "Reintentando factura #{$invoice->id}"
                );

                /*
                 * TRUE = intento automático.
                 */
                $service->emitir(
                    $invoice->fresh(),
                    true
                );

            } catch (\Throwable $e) {

                Log::error(
                    'Error en reintento automático ARCA.',
                    [
                        'invoice_id' =>
                            $invoice->id,

                        'message' =>
                            $e->getMessage(),

                        'exception' =>
                            get_class($e),
                    ]
                );

                $this->error(
                    'Factura #'
                    . $invoice->id
                    . ': '
                    . $e->getMessage()
                );
            }
        }

        return self::SUCCESS;
    }
}