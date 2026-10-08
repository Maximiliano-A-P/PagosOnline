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
             * DATOS DE CONTACTO
             * =====================================================
             *
             * Todos son opcionales (texto simple).
             */

            // Teléfono de contacto.
            $table->string('phone')
                ->nullable();

            // Email de contacto.
            $table->string('email')
                ->nullable();

            // Dirección del cliente.
            $table->string('address')
                ->nullable();


            /*
             * =====================================================
             * BLOQUEO (BORRADO LEGAL)
             * =====================================================
             *
             * Los clientes no se eliminan al instante: se bloquean
             * y se conservan 5 años por obligación legal. Pasado
             * ese plazo se borran (comando clients:purge-blocked).
             *
             * Los clientes bloqueados solo son accesibles
             * directamente desde la base de datos.
             */

            // true = cliente bloqueado.
            $table->boolean('blocked')
                ->default(false);

            // Momento en que se bloqueó (cuenta los 5 años).
            $table->timestamp('blocked_at')
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