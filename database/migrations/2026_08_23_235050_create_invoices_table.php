<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de facturas.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {

            $table->id();

            $table->timestamps();


            /*
             * =====================================================
             * FECHA DE EMISIÓN
             * =====================================================
             *
             * Solo se guarda la fecha.
             *
             * Es independiente de created_at.
             */
            $table->date('issued_at');


            /*
             * =====================================================
             * DATOS HISTÓRICOS DEL CLIENTE
             * =====================================================
             *
             * No utilizamos client_id.
             *
             * La factura conserva una copia de los datos del
             * cliente existentes en el momento de su emisión.
             */
            $table->string('client_name');

            $table->unsignedBigInteger('client_document');


            /*
             * =====================================================
             * DATOS DEL SERVICIO
             * =====================================================
             *
             * service_id identifica internamente el servicio
             * cuando este todavía existe en el sistema.
             *
             * Es NULL para facturas históricas cargadas
             * manualmente que no estén asociadas a un servicio
             * existente.
             *
             * Si posteriormente se elimina el servicio,
             * service_id pasa a NULL y service_name conserva
             * el nombre histórico de la factura.
             */
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();


            /*
             * Nombre histórico del servicio al momento
             * de emitir la factura.
             */
            $table->string('service_name');


            /*
             * =====================================================
             * DATOS ECONÓMICOS
             * =====================================================
             *
             * Estos valores son una copia de los valores del
             * servicio en el momento de generar la factura.
             */
            $table->decimal('price', 12, 2);

            /*
             * Fecha de vencimiento de la factura.
             */
            $table->date('due_date');

            /*
             * Precio correspondiente después del vencimiento.
             */
            $table->decimal('overdue_price', 12, 2);


            /*
             * =====================================================
             * ESTADO DEL PAGO
             * =====================================================
             *
             * pending = pendiente
             * paid    = pagada
             */
            $table->string('payment_status')
                ->default('pending');


            /*
             * Importe realmente pagado.
             *
             * NULL mientras no se haya registrado el pago.
             */
            $table->decimal('amount_paid', 12, 2)
                ->nullable();


            /*
             * Fecha en que se registró el pago.
             *
             * Solo se guarda la fecha, no la hora.
             */
            $table->date('paid_at')
                ->nullable();


            /*
             * Forma de pago.
             */
            $table->string('payment_method')
                ->nullable();


            /*
             * Usuario administrador que registró manualmente
             * el pago.
             *
             * NULL cuando el pago fue registrado automáticamente.
             */
            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            /*
             * =====================================================
             * DATOS ARCA
             * =====================================================
             */

            /*
             * Estado de la comunicación/emisión ante ARCA.
             */
            $table->string('arca_status')
                ->nullable();


            /*
             * Código de autorización electrónico.
             */
            $table->string('arca_cae')
                ->nullable();


            /*
             * Vencimiento del CAE.
             *
             * ARCA puede requerir fecha y hora.
             */
            $table->timestamp('arca_cae_expires_at')
                ->nullable();


            /*
             * Tipo de comprobante emitido.
             */
            $table->string('arca_invoice_type')
                ->nullable();


            /*
             * Punto de venta utilizado ante ARCA.
             */
            $table->unsignedInteger('arca_point_of_sale')
                ->nullable();


            /*
             * Número del comprobante asignado por ARCA.
             */
            $table->unsignedBigInteger('arca_invoice_number')
                ->nullable();


            /*
             * Información utilizada para generar/mostrar
             * el QR del comprobante.
             */
            $table->text('arca_qr')
                ->nullable();
        });
    }

    /**
     * Elimina la tabla de facturas.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};