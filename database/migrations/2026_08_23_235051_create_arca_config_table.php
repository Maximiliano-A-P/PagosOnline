<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arca_config', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->unsignedSmallInteger('condicion_iva')->nullable();

            // Punto de venta para comprobantes nacionales (WSFEv1).
            $table->unsignedInteger('punto_venta')->nullable();

            // Punto de venta habilitado para exportaciones (WSFEXv1).
            $table->unsignedInteger('punto_venta_exportacion')->nullable();

            // Credenciales WSFE.
            $table->text('token')->nullable();
            $table->text('sign')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Credenciales WSFEX.
            $table->text('token_wsfex')->nullable();
            $table->text('sign_wsfex')->nullable();
            $table->timestamp('token_wsfex_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arca_config');
    }
};