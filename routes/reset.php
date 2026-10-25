<?php

use App\Http\Controllers\Admin\ResetDatabaseController;
use Illuminate\Support\Facades\Route;

/*
 * Ruta para reconstruir la base de datos (SOLO desarrollo local).
 *
 * Este archivo NO se sube al repositorio. bootstrap/app.php lo carga
 * únicamente si existe.
 *
 * - No usa el grupo "web" (sin sesión ni auth) a propósito: si la
 *   base está rota o vacía, la sesión y el login no podrían funcionar
 *   y justo ahí hace falta poder resetearla.
 * - Como contra-seguro, no se registra en producción.
 */
if (app()->environment('production')) {
    return;
}

Route::get('/admin/resetDatabase', ResetDatabaseController::class)
    ->name('admin.reset-database');
