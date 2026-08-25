<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     *
     * Esta tabla guarda la configuración necesaria para que
     * el sistema pueda comunicarse con ARCA.
     *
     * No guarda información de facturas.
     * La información específica de cada factura se guarda
     * en la tabla invoices.
     */
    public function up(): void
    {
        Schema::create('arca_config', function (Blueprint $table) {

            // Identificador único de la configuración.
            $table->id();

            // Fechas de creación y última modificación.
            $table->timestamps();

            // CUIT del contribuyente ante ARCA.
            //
            // Se almacena como string porque el CUIT contiene
            // guiones cuando se muestra en formato habitual
            // (30-12345678-9).
            $table->string('cuit');

            // Ruta donde se encuentra almacenado el certificado
            // digital utilizado para comunicarse con ARCA.
            $table->string('certificate_path');

            // Ruta donde se encuentra almacenada la clave privada.
            $table->string('private_key_path');

            // Token utilizado para autenticarse contra ARCA.
            $table->text('token');

            // Fecha y hora de vencimiento del token.
            $table->timestamp('token_expires_at')->nullable();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('arca_config');
    }
};