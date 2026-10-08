<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeBlockedClients extends Command
{
    protected $signature = 'clients:purge-blocked';

    protected $description =
        'Elimina definitivamente los clientes bloqueados hace 5 años o más';

    public function handle(): int
    {
        $limite = now()->subYears(5);

        /*
         * Los clientes bloqueados están ocultos por el global scope, por eso se consulta sin él.
         */
        $ids = Client::withoutGlobalScopes()
            ->where('blocked', true)
            ->whereNotNull('blocked_at')
            ->where('blocked_at', '<=', $limite)
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('No hay clientes bloqueados para eliminar.');

            return self::SUCCESS;
        }

        /*
         * Se borra SOLO el cliente y sus servicios asignados
         * (client_services). Las facturas no se tocan: guardan sus
         * propios datos históricos y deben conservarse por ley.
         */
        DB::transaction(function () use ($ids) {
            DB::table('client_services')
                ->whereIn('client_id', $ids)
                ->delete();

            DB::table('client_user')
                ->whereIn('client_id', $ids)
                ->delete();

            Client::withoutGlobalScopes()
                ->whereIn('id', $ids)
                ->delete();
        });

        $this->info("Clientes eliminados definitivamente: {$ids->count()}");

        return self::SUCCESS;
    }
}