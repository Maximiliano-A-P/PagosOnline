<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /*
             * Permite que las cuentas creadas mediante Google
             * no tengan contraseña inicialmente.
             */
            $table->string('password')
                ->nullable()
                ->change();

            /*
             * Identificador de Google utilizado para las cuentas
             * vinculadas mediante OAuth.
             */
            $table->string('google_id')
                ->nullable()
                ->unique()
                ->after('email');

            /*
             * Indica si el usuario tiene permisos administrativos.
             */
            $table->boolean('is_admin')
                ->default(false)
                ->after('google_id');

            /*
             * Momento en que se envió el último código de
             * verificación de correo.
             *
             * Se utiliza para aplicar el cooldown por cuenta.
             */
            $table->timestamp('email_verification_sent_at')
                ->nullable()
                ->after('email_verified_at');

            /*
             * Momento en que se envió el último código para
             * añadir una contraseña a una cuenta de Google.
             *
             * Se utiliza para aplicar el cooldown por cuenta.
             */
            $table->timestamp('password_setup_sent_at')
                ->nullable()
                ->after('email_verification_sent_at');

            /*
             * Cantidad de intentos fallidos de inicio de sesión
             * de la cuenta.
             */
            $table->unsignedTinyInteger('login_attempts')
                ->default(0)
                ->after('password_setup_sent_at');

            /*
             * Momento hasta el cual la cuenta permanece bloqueada
             * después de alcanzar el límite de intentos.
             */
            $table->timestamp('login_locked_until')
                ->nullable()
                ->after('login_attempts');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'is_admin',
                'email_verification_sent_at',
                'password_setup_sent_at',
                'login_attempts',
                'login_locked_until',
            ]);

            /*
             * Restauramos el comportamiento original de Breeze/Laravel:
             * la contraseña vuelve a ser obligatoria.
             */
            $table->string('password')
                ->nullable(false)
                ->change();
        });
    }
};
