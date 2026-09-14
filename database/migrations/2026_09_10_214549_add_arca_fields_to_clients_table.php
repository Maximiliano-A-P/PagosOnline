<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los datos fiscales del cliente, necesarios para
     * determinar qué tipo de comprobante corresponde emitirle.
     *
     * Ambas columnas son nullable a propósito: al crear un
     * cliente no se conoce su condición fiscal todavía. El
     * sistema trata a un cliente sin estos datos como
     * Consumidor Final, hasta que alguien confirme lo contrario.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {

            // Código AFIP del tipo de documento (80=CUIT, 96=DNI,
            // 99=Consumidor Final). NULL = no se cargó todavía.
            $table->unsignedSmallInteger('arca_document_type')
                ->nullable()
                ->after('document');

            // Condición frente al IVA del cliente (código AFIP:
            // 1=Responsable Inscripto, 5=Consumidor Final,
            // 6=Monotributista, etc.). NULL = desconocida,
            // se trata como Consumidor Final por defecto.
            $table->unsignedSmallInteger('arca_iva_condition')
                ->nullable()
                ->after('arca_document_type');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['arca_document_type', 'arca_iva_condition']);
        });
    }
};