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

            /*
             * =====================================================
             * IDENTIFICADOR Y FECHAS
             * =====================================================
             */

            // Identificador único de la configuración.
            $table->id();

            // Fechas de creación y última modificación.
            $table->timestamps();


            /*
             * =====================================================
             * DATOS DEL CONTRIBUYENTE
             * =====================================================
             */

            // CUIT del contribuyente ante ARCA.
            //
            // Se almacena como string porque el CUIT contiene
            // guiones cuando se muestra en formato habitual
            // (30-12345678-9).
            $table->string('cuit');


            /*
             * =====================================================
             * CONFIGURACIÓN FISCAL DEL EMISOR
             * =====================================================
             */

            // Condición frente al IVA del emisor.
            //
            // Códigos AFIP habituales:
            // 1 = Responsable Inscripto
            // 6 = Monotributista
            // etc.
            $table->unsignedSmallInteger('condicion_iva')
                ->nullable()
                ->after('cuit');

            // Punto de venta habilitado en ARCA.
            //
            // Se guarda como configuración general de la empresa,
            // no como dato específico de cada factura.
            $table->unsignedInteger('punto_venta')
                ->nullable()
                ->after('condicion_iva');


            /*
             * =====================================================
             * AUTENTICACIÓN WSAA
             * =====================================================
             */

            // Token utilizado para autenticarse contra ARCA.
            //
            // Nullable: la fila de configuración se puede crear
            // antes de que el sistema se autentique por primera vez
            // contra el WSAA (recién ahí se completa solo).
            $table->text('token')->nullable();

            // Sign devuelto por el WSAA junto con el Token.
            //
            // Ambos son obligatorios para realizar las llamadas
            // posteriores a los servicios de ARCA.
            $table->text('sign')
                ->nullable()
                ->after('token');

            // Fecha y hora de vencimiento del token.
            $table->timestamp('token_expires_at')
                ->nullable();
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