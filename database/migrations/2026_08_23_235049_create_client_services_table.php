<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla que relaciona clientes con servicios.
     */
    public function up(): void
    {
        Schema::create('client_services', function (Blueprint $table) {
            // Identificador único de la relación.
            $table->id();

            // Cliente que tiene contratado el servicio.
            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            // Servicio contratado por el cliente.
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();

            // created_at y updated_at.
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla client_services.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_services');
    }
};