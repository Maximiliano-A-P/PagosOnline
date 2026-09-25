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

            /*
             * =====================================================
             * IDENTIFICADOR
             * =====================================================
             */

            // Identificador único del cliente.
            $table->id();


            /*
             * =====================================================
             * DATOS DEL CLIENTE
             * =====================================================
             */

            // Nombre completo del cliente.
            $table->string('name');

            // Documento numérico del cliente.
            $table->unsignedBigInteger('document');

            // CUIT del cliente.
            // NULL = el cliente no tiene CUIT cargada.
            $table->string('cuit', 11)
                ->nullable();


            /*
             * =====================================================
             * DATOS FISCALES ARCA
             * =====================================================
             *
             * Estos datos permiten determinar qué tipo de
             * comprobante corresponde emitir al cliente.
             *
             * La condición frente al IVA puede no conocerse
             * todavía al crear el cliente.
             */

            // Condición frente al IVA:
            // 1 = Responsable Inscripto
            // 5 = Consumidor Final
            // 6 = Monotributista
            // etc.
            //
            // NULL = condición desconocida.
            $table->unsignedSmallInteger('arca_iva_condition')
                ->nullable()
                ->after('cuit');


            /*
             * =====================================================
             * FECHAS
             * =====================================================
             */

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