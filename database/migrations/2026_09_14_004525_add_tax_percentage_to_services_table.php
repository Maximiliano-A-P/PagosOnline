<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el porcentaje de impuesto (IVA u otro) a aplicar
     * sobre el precio neto del servicio. Es configurable porque
     * la alícuota puede cambiar por ley en cualquier momento.
     *
     * NULL/0 = no se aplica impuesto (ej. Monotributista).
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('tax_percentage', 5, 2)
                ->nullable()
                ->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('tax_percentage');
        });
    }
};