<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'is_admin',
    'email_verified_at',
    'email_verification_sent_at',
    'password_setup_sent_at',
    'login_attempts',
    'login_locked_until',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'password_setup_sent_at' => 'datetime',
            'login_locked_until' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Códigos utilizados para establecer una contraseña
     * en cuentas creadas inicialmente mediante Google.
     */
    public function passwordSetupCodes()
    {
        return $this->hasMany(PasswordSetupCode::class);
    }

    /**
     * Códigos utilizados para verificar el correo
     * de las cuentas registradas mediante Breeze.
     */
    public function emailVerificationCodes()
    {
        return $this->hasMany(EmailVerificationCode::class);
    }

    /**
     * Tokens utilizados para desbloquear la cuenta
     * después de alcanzar el límite de intentos de login.
     */
    public function loginUnlockTokens()
    {
        return $this->hasMany(LoginUnlockToken::class);
    }
}