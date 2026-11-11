<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arca_config', function (Blueprint $table) {
            $table->unsignedSmallInteger('tipo_factura_a')
                ->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('arca_config', function (Blueprint $table) {
            $table->dropColumn('tipo_factura_a');
        });
    }
};