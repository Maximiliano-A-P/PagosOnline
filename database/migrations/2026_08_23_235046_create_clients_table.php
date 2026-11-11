<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Datos generales.
            $table->string('name');
            $table->unsignedBigInteger('document');

            // Datos fiscales argentinos.
            $table->string('cuit', 11)->nullable();
            $table->unsignedSmallInteger('arca_iva_condition')
                ->nullable();

            /*
             * Datos fiscales del comprador del exterior.
             *
             * arca_destination_country: código de país de destino
             * del catálogo que utiliza ARCA.
             *
             * arca_cuit_pais_cliente: identificador fiscal del
             * comprador en su país, cuando corresponda.
             *
             * arca_id_impositivo: identificador fiscal alternativo.
             */
            $table->unsignedSmallInteger('arca_destination_country')
                ->nullable();

            $table->string('arca_cuit_pais_cliente', 11)
                ->nullable();

            $table->string('arca_id_impositivo', 50)
                ->nullable();

            // Contacto.
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            // Bloqueo legal.
            $table->boolean('blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};