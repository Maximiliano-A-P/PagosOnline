<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     * Crea la tabla pivote para relacionar usuarios con múltiples clientes/documentos.
     */
    public function up(): void
    {
        Schema::create('client_user', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Relación con la tabla users
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Relación con la tabla clients (asumiendo que 'id' es la PK de clients)
            // Si prefieres relacionarlo directamente por el documento, puedes cambiarlo, 
            // pero usar client_id es lo estándar en Laravel.
            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            // Opcional: Para evitar que se duplique la misma asignación
            $table->unique(['user_id', 'client_id']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_user');
    }
};