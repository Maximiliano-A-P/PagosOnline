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


            /*
             * =====================================================
             * DATOS FISCALES ARCA
             * =====================================================
             *
             * Estos datos permiten determinar qué tipo de
             * comprobante corresponde emitir al cliente.
             *
             * Ambos campos son nullable a propósito: al crear un
             * cliente puede no conocerse todavía su condición fiscal.
             *
             * El sistema trata a un cliente sin estos datos como
             * Consumidor Final hasta que alguien confirme lo contrario.
             */

            // Código AFIP del tipo de documento:
            // 80 = CUIT
            // 96 = DNI
            // 99 = Consumidor Final
            //
            // NULL = todavía no se cargó.
            $table->unsignedSmallInteger('arca_document_type')
                ->nullable()
                ->after('document');

            // Condición frente al IVA:
            // 1 = Responsable Inscripto
            // 5 = Consumidor Final
            // 6 = Monotributista
            // etc.
            //
            // NULL = condición desconocida.
            $table->unsignedSmallInteger('arca_iva_condition')
                ->nullable()
                ->after('arca_document_type');


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