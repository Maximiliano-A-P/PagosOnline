<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class ResetDatabaseController extends Controller
{
    /**
     * Reconstruye completamente la base de datos.
     *
     * Elimina las tablas existentes, ejecuta nuevamente
     * todas las migraciones y carga los seeders.
     */
    public function __invoke()
    {
        try {
            Artisan::call('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
            ]);

            return $this->text(
                "Base de datos reconstruida correctamente.\n\n"
                . Artisan::output()
            );
        } catch (Throwable $e) {
            report($e);

            return $this->text(
                'No se pudo reconstruir la base de datos. '
                . 'Revisá los registros de Laravel.',
                500
            );
        }
    }

    /**
     * Respuesta en texto plano.
     *
     * Laravel no trae response()->text(), por eso se arma a mano.
     */
    private function text(string $content, int $status = 200)
    {
        return response($content, $status)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}

/*
 * URL para ejecutar el reinicio:
 * /admin/resetDatabase
 */