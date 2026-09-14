<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el monto exacto (base + IVA) que se le informó a
     * Mercado Pago al generar el link de pago. Es la fuente de
     * verdad para validar el pago recibido en el webhook, sin
     * depender de recalcular fechas después del hecho.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('expected_amount', 10, 2)
                ->nullable()
                ->after('overdue_price');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('expected_amount');
        });
    }
};