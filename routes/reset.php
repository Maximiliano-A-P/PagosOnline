<?php

use App\Http\Controllers\Admin\ResetDatabaseController;
use Illuminate\Support\Facades\Route;

/*
 * Ruta para reconstruir la base de datos (demo online).
 *
 * Solo puede ejecutarla un usuario con sesión iniciada y permisos
 * de administrador (mismo control que el resto del panel admin).
 *
 * bootstrap/app.php carga este archivo.
 */
Route::middleware(['web', 'auth', 'admin'])
    ->get('/admin/resetDatabase', ResetDatabaseController::class)
    ->name('admin.reset-database');