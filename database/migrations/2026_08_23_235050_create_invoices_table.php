<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->date('issued_at');

            /*
             * Datos históricos del cliente.
             */
            $table->string('client_name');
            $table->unsignedBigInteger('client_document');
            $table->string('client_cuit', 11)->nullable();
            $table->unsignedSmallInteger('client_document_type')
                ->nullable();
            $table->unsignedSmallInteger('client_iva_condition')
                ->nullable();

            /*
             * Snapshot de los datos del comprador extranjero.
             */
            $table->string('client_address')->nullable();

            $table->unsignedSmallInteger('client_arca_destination_country')
                ->nullable();

            $table->string('client_arca_cuit_pais_cliente', 11)
                ->nullable();

            $table->string('client_arca_id_impositivo', 50)
                ->nullable();

            /*
             * Servicio asociado.
             */
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            $table->string('service_name');

            $table->date('service_period_start')->nullable();
            $table->date('service_period_end')->nullable();

            /*
             * Datos económicos.
             */
            $table->decimal('price', 12, 2);
            $table->date('due_date');
            $table->decimal('overdue_price', 12, 2);
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->decimal('expected_amount', 10, 2)->nullable();

            /*
             * Estado del pago.
             */
            $table->string('payment_status')->default('pending');
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable();

            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Mercado Pago.
             */
            $table->string('mercadopago_preference_id')->nullable();
            $table->string('mercadopago_payment_id')->nullable();

            /*
             * ARCA.
             */
            $table->string('arca_status')->nullable();
            $table->string('arca_cae')->nullable();
            $table->timestamp('arca_cae_expires_at')->nullable();
            $table->string('arca_invoice_type')->nullable();
            $table->unsignedInteger('arca_point_of_sale')->nullable();
            $table->unsignedBigInteger('arca_invoice_number')->nullable();
            $table->text('arca_qr')->nullable();

            /*
             * Reintentos de emisión.
             */
            $table->unsignedInteger('arca_retry_attempts')->default(0);
            $table->timestamp('arca_last_attempt_at')->nullable();
            $table->timestamp('arca_retry_at')->nullable();

            /*
             * Envío de comprobante por correo.
             */
            $table->timestamp('receipt_sent_at')->nullable();

            $table->index([
                'payment_status',
                'arca_retry_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};