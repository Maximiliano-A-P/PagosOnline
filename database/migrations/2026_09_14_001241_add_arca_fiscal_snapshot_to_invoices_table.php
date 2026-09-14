<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega a invoices una copia histórica de los datos
     * fiscales del cliente, siguiendo el mismo criterio que ya
     * usan client_name/client_document: la factura conserva los
     * datos vigentes al momento de emitirse, sin depender de que
     * el registro de Client no cambie después.
     *
     * También agrega el período de servicio facturado, exigido
     * por ARCA para comprobantes de Concepto 2 (Servicios).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            // Snapshot: código AFIP del tipo de documento del
            // cliente al momento de facturar. NULL = se trató
            // como Consumidor Final.
            $table->unsignedSmallInteger('client_document_type')
                ->nullable()
                ->after('client_document');

            // Snapshot: condición frente al IVA del cliente al
            // momento de facturar. NULL = se trató como
            // Consumidor Final.
            $table->unsignedSmallInteger('client_iva_condition')
                ->nullable()
                ->after('client_document_type');

            // Período de servicio que cubre esta factura
            // (exigido por WSFE para Concepto 2/3).
            $table->date('service_period_start')
                ->nullable()
                ->after('service_name');

            $table->date('service_period_end')
                ->nullable()
                ->after('service_period_start');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'client_document_type',
                'client_iva_condition',
                'service_period_start',
                'service_period_end',
            ]);
        });
    }
};