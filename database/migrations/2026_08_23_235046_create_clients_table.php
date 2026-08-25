<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla clients.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            // Identificador único del cliente.
            $table->id();

            // Nombre completo del cliente.
            $table->string('name');

            // Documento numérico del cliente.
            $table->unsignedBigInteger('document');

            // created_at y updated_at.
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla clients.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};