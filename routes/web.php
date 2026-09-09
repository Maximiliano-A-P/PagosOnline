<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\PasswordSetupController;
use App\Http\Controllers\LoginUnlockController;

// Admin
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ClientServiceController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ArcaConfigController;

// Dashboard
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardInvoiceController;
use App\Http\Controllers\MercadoPagoWebhookController;


// ==========================================================
// Principal
// ==========================================================

Route::get('/', function () {
    return view('welcome');
});


// ==========================================================
// Dashboard
// ==========================================================

Route::middleware(['auth'])
    ->group(function () {

        Route::get(
            '/dashboard',
            [DashboardController::class, 'index']
        )->name('dashboard');


        // ==================================================
        // Documentos del dashboard
        // ==================================================

        Route::post(
            '/dashboard/documents',
            [DashboardController::class, 'storeDocument']
        )->name('dashboard.documents.store');


        Route::delete(
            '/dashboard/documents/{document}',
            [DashboardController::class, 'destroyDocument']
        )->name('dashboard.documents.destroy');


        // ==================================================
        // Facturas del dashboard
        // ==================================================

        Route::get(
            '/dashboard/invoices/{invoice}/pay',
            [DashboardInvoiceController::class, 'pay']
        )->name('dashboard.invoices.pay');


        Route::get(
            '/dashboard/invoices/{invoice}/pdf',
            function ($invoice) {
                /*
                 * Generación del PDF se implementará posteriormente.
                 */
                abort(501, 'Generación de PDF todavía no implementada.');
            }
        )->name('dashboard.invoices.pdf');


        Route::get(
            '/dashboard/invoices/history',
            [DashboardInvoiceController::class, 'history']
        )->name('dashboard.invoices.history');

    });


// ==========================================================
// Webhook Mercado Pago
// ==========================================================

Route::post(
    '/mercadopago/webhook',
    [MercadoPagoWebhookController::class, 'handle']
)->name('mercadopago.webhook');


// ==========================================================
// Perfil
// ==========================================================

Route::middleware('auth')->group(function () {

    Route::get('/profile', [
        ProfileController::class,
        'edit'
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update'
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy'
    ])->name('profile.destroy');
});


// ==========================================================
// Autenticación Breeze
// ==========================================================

require __DIR__.'/auth.php';


// ==========================================================
// Panel Admin
// ==========================================================

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/', [
            AdminDashboardController::class,
            'index'
        ])->name('dashboard');


        // ==================================================
        // Clientes
        // ==================================================

        Route::resource(
            'clients',
            ClientController::class
        )->except(['show']);


        // ==================================================
        // Servicios
        // ==================================================

        Route::resource(
            'services',
            ServiceController::class
        )->except(['show']);


        // ==================================================
        // Clientes x Servicios
        // ==================================================

        Route::get(
            '/client-services',
            [ClientServiceController::class, 'index']
        )->name('client-services.index');

        Route::get(
            '/client-services/{client}/create',
            [ClientServiceController::class, 'create']
        )->name('client-services.create');

        Route::post(
            '/client-services/{client}',
            [ClientServiceController::class, 'store']
        )->name('client-services.store');

        Route::delete(
            '/client-services/{client}',
            [ClientServiceController::class, 'destroy']
        )->name('client-services.destroy');


        // ==================================================
        // Facturas
        // ==================================================

        Route::resource(
            'invoices',
            InvoiceController::class
        )->only([
            'index',
            'create',
            'store',
            'show',
            'destroy',
        ]);


        // Generar todas las facturas periódicas que correspondan
        Route::post(
            '/invoices/generate',
            [InvoiceController::class, 'generate']
        )->name('invoices.generate');


        // Formulario para registrar pago manual
        Route::get(
            '/invoices/{invoice}/payment',
            [InvoiceController::class, 'payment']
        )->name('invoices.payment');


        // Registrar pago manual
        Route::post(
            '/invoices/{invoice}/pay',
            [InvoiceController::class, 'registerPayment']
        )->name('invoices.pay');


        // ==================================================
        // Configuración ARCA
        // ==================================================

        Route::get(
            '/arca',
            [ArcaConfigController::class, 'edit']
        )->name('arca.edit');

        Route::put(
            '/arca',
            [ArcaConfigController::class, 'update']
        )->name('arca.update');

    });


// ==========================================================
// Google Socialite
// ==========================================================

Route::get(
    '/auth/google',
    [SocialiteController::class,
    'redirectToGoogle']
)->name('google.login');

Route::get(
    '/auth/google/callback',
    [SocialiteController::class,
    'handleGoogleCallback']
);


// ==========================================================
// Configuración de contraseña
// ==========================================================

Route::get(
    '/password/setup',
    [PasswordSetupController::class,
    'show']
)->name('password.setup');

Route::post(
    '/password/setup/send',
    [PasswordSetupController::class,
    'sendCode']
)->name('password.setup.send');

Route::get(
    '/password/setup/verify',
    [PasswordSetupController::class,
    'showVerifyForm']
)->name('password.setup.verify');

Route::post(
    '/password/setup/verify',
    [PasswordSetupController::class,
    'verifyCode']
)->name('password.setup.verify.code');


// ==========================================================
// Desbloqueo de cuenta
// ==========================================================

Route::get(
    '/login/unlock/{token}',
    [LoginUnlockController::class,
    'unlock']
)->name('login.unlock');