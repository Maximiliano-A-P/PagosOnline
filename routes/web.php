<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\PasswordSetupController;
use App\Http\Controllers\LoginUnlockController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


// //////Socialite//////
Route::get('/auth/google', [SocialiteController::class, 'redirectToGoogle'])
    ->name('google.login');

Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);


// //////Configuración de contraseña para cuentas creadas con Google//////

Route::get('/password/setup', [PasswordSetupController::class, 'show'])
    ->name('password.setup');

Route::post('/password/setup/send', [PasswordSetupController::class, 'sendCode'])
    ->name('password.setup.send');

Route::get('/password/setup/verify', [PasswordSetupController::class, 'showVerifyForm'])
    ->name('password.setup.verify');

Route::post('/password/setup/verify', [PasswordSetupController::class, 'verifyCode'])
    ->name('password.setup.verify.code');

//////Desbloqueo de cuenta//////

Route::get('/login/unlock/{token}', [LoginUnlockController::class, 'unlock'])
    ->name('login.unlock');