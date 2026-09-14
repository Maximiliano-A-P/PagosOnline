<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega a arca_config los datos que faltaban para poder
     * autenticarse y facturar correctamente ante ARCA:
     *
     * - sign: el WSAA devuelve Token Y Sign, ambos obligatorios
     *   para cada llamada a WSFE. Faltaba esta columna.
     *
     * - condicion_iva: la condición del EMISOR (tu empresa)
     *   frente al IVA. Se cruza con la del cliente en
     *   ComprobanteResolver para decidir la letra A/B/C.
     *
     * - punto_venta: el punto de venta habilitado en ARCA que
     *   usa esta empresa para emitir. Se guarda acá porque es
     *   un dato de configuración general, no de cada factura.
     */
    public function up(): void
    {
        Schema::table('arca_config', function (Blueprint $table) {

            // Sign devuelto por el WSAA junto con el Token.
            $table->text('sign')
                ->nullable()
                ->after('token');

            // Condición frente al IVA del emisor (código AFIP:
            // 1=Responsable Inscripto, 6=Monotributista, etc.).
            $table->unsignedSmallInteger('condicion_iva')
                ->nullable()
                ->after('cuit');

            // Punto de venta habilitado en ARCA.
            $table->unsignedInteger('punto_venta')
                ->nullable()
                ->after('condicion_iva');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('arca_config', function (Blueprint $table) {
            $table->dropColumn([
                'sign',
                'condicion_iva',
                'punto_venta',
            ]);
        });
    }
};