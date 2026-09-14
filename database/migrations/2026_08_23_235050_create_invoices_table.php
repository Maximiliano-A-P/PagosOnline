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

            /*
             * =====================================================
             * IDENTIFICADOR Y FECHAS
             * =====================================================
             */

            // Identificador único de la factura.
            $table->id();

            // created_at y updated_at.
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

            // Nombre histórico del cliente.
            $table->string('client_name');

            // Documento histórico del cliente.
            $table->unsignedBigInteger('client_document');

            /*
             * Snapshot: código AFIP del tipo de documento del
             * cliente al momento de facturar.
             *
             * NULL = se trató como Consumidor Final.
             */
            $table->unsignedSmallInteger('client_document_type')
                ->nullable();

            /*
             * Snapshot: condición frente al IVA del cliente al
             * momento de facturar.
             *
             * NULL = se trató como Consumidor Final.
             */
            $table->unsignedSmallInteger('client_iva_condition')
                ->nullable();


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
             * Período de servicio que cubre esta factura.
             *
             * Es exigido por WSFE para Concepto 2/3.
             */
            $table->date('service_period_start')
                ->nullable();

            $table->date('service_period_end')
                ->nullable();


            /*
             * =====================================================
             * DATOS ECONÓMICOS
             * =====================================================
             *
             * Estos valores son una copia de los valores del
             * servicio en el momento de generar la factura.
             */

            // Precio normal del servicio al momento de facturar.
            $table->decimal('price', 12, 2);

            // Fecha de vencimiento de la factura.
            $table->date('due_date');

            // Precio correspondiente después del vencimiento.
            $table->decimal('overdue_price', 12, 2);

            /*
             * Snapshot: porcentaje de impuesto (IVA) vigente
             * en el Service al momento de generar la factura.
             */
            $table->decimal('tax_percentage', 5, 2)
                ->nullable();

            /*
             * Guarda el monto exacto (base + IVA) que se le informó
             * a Mercado Pago al generar el link de pago.
             *
             * Es la fuente de verdad para validar el pago recibido
             * en el webhook, sin depender de recalcular fechas
             * después del hecho.
             */
            $table->decimal('expected_amount', 10, 2)
                ->nullable();


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
             * DATOS MERCADO PAGO
             * =====================================================
             *
             * preference_id identifica la preferencia de pago
             * creada en Mercado Pago para esta factura.
             *
             * payment_id identifica el pago concreto realizado
             * en Mercado Pago.
             *
             * Ambos campos son NULL mientras la factura no haya
             * iniciado o completado un pago mediante Mercado Pago.
             */

            $table->string('mercadopago_preference_id')
                ->nullable();

            $table->string('mercadopago_payment_id')
                ->nullable();


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