<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla services.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            // Nombre del servicio.
            $table->string('service');

            // Precio normal.
            $table->decimal('price', 12, 2);

            // Día del mes en que vence el servicio.
            $table->unsignedTinyInteger('due_day');

            // Precio que corresponde después del vencimiento.
            $table->decimal('overdue_price', 12, 2);

            // Cada cuántos meses se genera una factura.
            //
            // 1  = mensual
            // 3  = trimestral
            // 6  = semestral
            // 12 = anual
            $table->unsignedInteger('period');

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla services.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};