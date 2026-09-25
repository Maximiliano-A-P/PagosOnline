<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la CUIT opcional del cliente
     * y su copia histórica en las facturas.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('cuit', 11)
                ->nullable()
                ->after('document');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('client_cuit', 11)
                ->nullable()
                ->after('client_document');
        });
    }

    /**
     * Revierte los cambios.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('client_cuit');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('cuit');
        });
    }
};

