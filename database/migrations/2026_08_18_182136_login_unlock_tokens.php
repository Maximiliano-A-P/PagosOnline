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
        Schema::create('login_unlock_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Guardamos solamente el hash del token.
             * El token original solamente se envía por correo.
             */
            $table->string('token', 64)->unique();

            /*
             * El enlace solamente puede utilizarse
             * durante un período limitado.
             */
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_unlock_tokens');
    }
};