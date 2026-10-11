<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca las facturas históricas (anteriores al uso del sistema).
     *
     * Las históricas quedan fuera del flujo de emisión ante ARCA:
     * ya fueron emitidas con el sistema anterior, así que emitirlas
     * de nuevo generaría un comprobante fiscal duplicado.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_historical')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('is_historical');
        });
    }
};