<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="font-semibold text-white leading-tight text-[32px]">
                Detalle de factura
            </h2>

            <a href="{{ route('admin.invoices.index') }}" class="btn">
                Volver
            </a>
        </div>
    </x-slot>

    <style>
        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 7px 14px;
            background-color: #111827;
            border: 1px solid #111827;
            border-radius: 6px;
            font-weight: 600;
            color: #ffffff;
            font-size: 18px;
            line-height: normal;
            font-family: inherit;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            transition: background-color 0.15s ease-in-out;
        }

        .btn:hover {
            background-color: #374151;
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563;
        }

        .btn-danger {
            background-color: #991b1b;
            border-color: #991b1b;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .card-header {
            background-color: #111827;
            padding: 20px 24px;
        }

        .card-header h3 {
            color: #ffffff;
            font-weight: 600;
            font-size: 24px;
            margin: 0;
        }

        .section-header {
            background-color: #111827;
            color: #ffffff;
            font-weight: 600;
            font-size: 22px;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 16px;
            color: #ffffff;
        }

        .badge-paid {
            background-color: #15803d;
        }

        .badge-pending {
            background-color: #111827;
        }

        .badge-approved {
            background-color: #15803d;
        }

        .badge-error {
            background-color: #b91c1c;
        }

        .retention-notice {
            border: 1px solid #d97706;
            background-color: #fffbeb;
            color: #92400e;
            padding: 16px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 18px;
        }

        .export-notice {
            border: 1px solid #93c5fd;
            background-color: #eff6ff;
            color: #1e40af;
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 17px;
        }

        .detail-label {
            color: #374151;
            font-size: 18px;
            font-weight: 600;
        }

        .detail-value {
            color: #111827;
            font-size: 19px;
            overflow-wrap: anywhere;
        }
    </style>

    @php
        $taxPercentage = (float) ($invoice->tax_percentage ?? 0);

        $priceWithTax = round(
            (float) $invoice->price * (1 + ($taxPercentage / 100)),
            2
        );

        $overduePriceWithTax = round(
            (float) $invoice->overdue_price * (1 + ($taxPercentage / 100)),
            2
        );

        $tipoComprobante = (int) ($invoice->arca_invoice_type ?? 0);

        $tiposComprobante = [
            1 => 'Factura A',
            2 => 'Nota de débito A',
            3 => 'Nota de crédito A',
            4 => 'Recibo A',
            5 => 'Nota de venta al contado A',

            6 => 'Factura B',
            7 => 'Nota de débito B',
            8 => 'Nota de crédito B',
            9 => 'Recibo B',
            10 => 'Nota de venta al contado B',

            11 => 'Factura C',
            12 => 'Nota de débito C',
            13 => 'Nota de crédito C',
            15 => 'Recibo C',

            19 => 'Factura E',
            20 => 'Nota de débito E',
            21 => 'Nota de crédito E',

            51 => 'Factura A sujeta a retención',
            52 => 'Nota de débito A sujeta a retención',
            53 => 'Nota de crédito A sujeta a retención',
        ];

        $letrasComprobante = [
            1 => 'A',
            2 => 'A',
            3 => 'A',
            4 => 'A',
            5 => 'A',

            6 => 'B',
            7 => 'B',
            8 => 'B',
            9 => 'B',
            10 => 'B',

            11 => 'C',
            12 => 'C',
            13 => 'C',
            15 => 'C',

            19 => 'E',
            20 => 'E',
            21 => 'E',
        ];

        $esExportacion = in_array($tipoComprobante, [19, 20, 21], true)
            || (int) ($invoice->client_iva_condition ?? 0) === 9;

        $esAConLeyendaRetencion = in_array(
            $tipoComprobante,
            [51, 52, 53],
            true
        );

        $nombreComprobante = $tiposComprobante[$tipoComprobante]
            ?? ($esExportacion ? 'Factura E' : 'Sin determinar');

        $letraComprobante = $letrasComprobante[$tipoComprobante]
            ?? ($esExportacion ? 'E' : null);

        $identificadorFiscalExtranjero =
            $invoice->client_arca_cuit_pais_cliente
            ?: $invoice->client_arca_id_impositivo;

        $condicionesIva = [
            1 => 'IVA Responsable Inscripto',
            4 => 'IVA Sujeto Exento',
            5 => 'Consumidor Final',
            6 => 'Responsable Monotributo',
            7 => 'Sujeto No Categorizado',
            9 => 'Cliente del exterior',
            13 => 'Monotributista Social',
            15 => 'IVA No Alcanzado',
            16 => 'Monotributo Trabajador Independiente Promovido',
        ];

        $condicionIva = $condicionesIva[
            (int) ($invoice->client_iva_condition ?? 0)
        ] ?? 'No especificada';

        $estadoArca = $invoice->arca_status ?: 'Sin procesar';

        $arcaAprobado = strtolower((string) $invoice->arca_status)
            === 'aprobado';

        $numeroComprobante = null;

        if ($invoice->arca_point_of_sale && $invoice->arca_invoice_number) {
            $numeroComprobante = str_pad(
                (string) $invoice->arca_point_of_sale,
                5,
                '0',
                STR_PAD_LEFT
            ) . '-' . str_pad(
                (string) $invoice->arca_invoice_number,
                8,
                '0',
                STR_PAD_LEFT
            );
        }
    @endphp

    <div class="py-12">
        <div class="mx-auto" style="width: 90vw; max-width: 1600px;">

            @if (session('success'))
                <div class="mb-8 rounded-lg bg-green-700 border border-green-800 text-white px-6 py-4 shadow-sm text-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-8 rounded-lg bg-red-700 border border-red-800 text-white px-6 py-4 shadow-sm text-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="card-header">
                    <h3>Información de la factura #{{ $invoice->id }}</h3>
                </div>

                <div class="p-6">

                    @if ($esAConLeyendaRetencion)
                        <div class="retention-notice mb-6">
                            OPERACIÓN SUJETA A RETENCIÓN
                        </div>
                    @endif

                    @if ($esExportacion)
                        <div class="export-notice mb-6">
                            <strong>Comprobante de exportación.</strong>
                            Este comprobante corresponde a un cliente del exterior
                            y se procesa mediante el servicio WSFEX de ARCA.
                            No se muestra el IVA nacional como en una factura A, B o C.
                        </div>
                    @endif

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div>
                            <dt class="detail-label">ID de factura</dt>
                            <dd class="mt-2 detail-value">#{{ $invoice->id }}</dd>
                        </div>

                        <div>
                            <dt class="detail-label">Fecha de emisión</dt>
                            <dd class="mt-2 detail-value">
                                {{ $invoice->issued_at?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Vencimiento</dt>
                            <dd class="mt-2 detail-value">
                                {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Cliente</dt>
                            <dd class="mt-2 detail-value">
                                {{ $invoice->client_name }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">
                                {{ $esExportacion ? 'Identificador fiscal extranjero' : 'Documento' }}
                            </dt>
                            <dd class="mt-2 detail-value">
                                @if ($esExportacion)
                                    {{ $identificadorFiscalExtranjero ?: '—' }}
                                @else
                                    {{ $invoice->client_document ?: '—' }}
                                @endif
                            </dd>
                        </div>

                        @unless ($esExportacion)
                            <div>
                                <dt class="detail-label">CUIT del cliente</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $invoice->client_cuit ?: '—' }}
                                </dd>
                            </div>
                        @endunless

                        <div>
                            <dt class="detail-label">Servicio</dt>
                            <dd class="mt-2 detail-value">
                                {{ $invoice->service_name }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">ID del servicio</dt>
                            <dd class="mt-2 detail-value">
                                {{ $invoice->service_id ?? 'Sin servicio asociado' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Precio</dt>
                            <dd class="mt-2 detail-value">
                                ${{ number_format((float) $invoice->price, 2, ',', '.') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Precio vencido</dt>
                            <dd class="mt-2 detail-value">
                                ${{ number_format((float) $invoice->overdue_price, 2, ',', '.') }}
                            </dd>
                        </div>

                        @unless ($esExportacion)
                            <div>
                                <dt class="detail-label">Impuestos</dt>
                                <dd class="mt-2 detail-value">
                                    {{ number_format($taxPercentage, 2, ',', '.') }}%
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">Precio con impuestos</dt>
                                <dd class="mt-2 detail-value">
                                    ${{ number_format($priceWithTax, 2, ',', '.') }}
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">Precio vencido con impuestos</dt>
                                <dd class="mt-2 detail-value">
                                    ${{ number_format($overduePriceWithTax, 2, ',', '.') }}
                                </dd>
                            </div>
                        @endunless

                        <div>
                            <dt class="detail-label">Condición frente al IVA</dt>
                            <dd class="mt-2 detail-value">
                                {{ $condicionIva }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Estado del pago</dt>
                            <dd class="mt-2">
                                @if ($invoice->payment_status === 'paid')
                                    <span class="badge badge-paid">Pagada</span>
                                @else
                                    <span class="badge badge-pending">Pendiente</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Importe pagado</dt>
                            <dd class="mt-2 detail-value">
                                @if ($invoice->amount_paid !== null)
                                    ${{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Fecha de pago</dt>
                            <dd class="mt-2 detail-value">
                                {{ $invoice->paid_at?->format('d/m/Y H:i') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="detail-label">Método de pago</dt>
                            <dd class="mt-2 detail-value">
                                @if ($invoice->payment_method === 'mercadopago')
                                    Mercado Pago
                                @else
                                    {{ $invoice->payment_method ?? '—' }}
                                @endif
                            </dd>
                        </div>

                    </dl>

                    {{-- Datos fiscales específicos del comprador extranjero --}}
                    @if ($esExportacion)
                        <div class="mt-12 pt-8 border-t border-gray-300">
                            <h3 class="section-header">
                                Datos de exportación
                            </h3>

                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">

                                <div>
                                    <dt class="detail-label">Domicilio del cliente extranjero</dt>
                                    <dd class="mt-2 detail-value">
                                        {{ $invoice->client_address ?: '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="detail-label">Código de país de destino ARCA</dt>
                                    <dd class="mt-2 detail-value">
                                        {{ $invoice->client_arca_destination_country ?: '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="detail-label">CUIT del país del cliente</dt>
                                    <dd class="mt-2 detail-value">
                                        {{ $invoice->client_arca_cuit_pais_cliente ?: '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="detail-label">Identificación impositiva del cliente</dt>
                                    <dd class="mt-2 detail-value">
                                        {{ $invoice->client_arca_id_impositivo ?: '—' }}
                                    </dd>
                                </div>

                            </dl>
                        </div>
                    @endif

                    {{-- Datos del comprobante ARCA --}}
                    <div class="mt-12 pt-8 border-t border-gray-300">
                        <h3 class="section-header">
                            Datos ARCA — {{ $esExportacion ? 'WSFEX' : 'WSFE' }}
                        </h3>

                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">

                            <div>
                                <dt class="detail-label">Estado ARCA</dt>
                                <dd class="mt-2">
                                    @if ($arcaAprobado)
                                        <span class="badge badge-approved">
                                            {{ $estadoArca }}
                                        </span>
                                    @elseif (
                                        in_array(
                                            strtolower((string) $invoice->arca_status),
                                            ['rechazado', 'error', 'fallido'],
                                            true
                                        )
                                    )
                                        <span class="badge badge-error">
                                            {{ $estadoArca }}
                                        </span>
                                    @else
                                        <span class="badge badge-pending">
                                            {{ $estadoArca }}
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">Tipo de comprobante</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $nombreComprobante }}

                                    @if ($tipoComprobante > 0)
                                        (tipo {{ $tipoComprobante }})
                                    @elseif ($esExportacion)
                                        (emisión pendiente)
                                    @endif

                                    @if ($letraComprobante)
                                        — Letra {{ $letraComprobante }}
                                    @endif
                                </dd>
                            </div>

                            @if ($esAConLeyendaRetencion)
                                <div class="md:col-span-2">
                                    <dt class="detail-label">Leyenda del comprobante</dt>
                                    <dd class="mt-2 detail-value font-bold">
                                        OPERACIÓN SUJETA A RETENCIÓN
                                    </dd>
                                </div>
                            @endif

                            <div>
                                <dt class="detail-label">Punto de venta</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $invoice->arca_point_of_sale ?? '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">Número de comprobante</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $numeroComprobante ?? '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">CAE</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $invoice->arca_cae ?: '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">Vencimiento del CAE</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $invoice->arca_cae_expires_at?->format('d/m/Y') ?? '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">Último intento de emisión</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $invoice->arca_last_attempt_at?->format('d/m/Y H:i') ?? '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="detail-label">Cantidad de reintentos</dt>
                                <dd class="mt-2 detail-value">
                                    {{ $invoice->arca_retry_attempts ?? 0 }}
                                </dd>
                            </div>

                        </dl>
                    </div>

                    {{-- Acciones --}}
                    <div class="mt-12 pt-8 border-t border-gray-300">
                        <div class="flex items-center gap-4 flex-wrap">

                            <a
                                href="{{ route('admin.invoices.pdf', $invoice) }}"
                                class="btn"
                            >
                                Descargar PDF
                            </a>

                            @if ($invoice->payment_status !== 'paid')
                                <a
                                    href="{{ route('admin.invoices.payment', $invoice) }}"
                                    class="btn"
                                >
                                    Registrar pago
                                </a>
                            @endif

                            @if (
                                $invoice->payment_status === 'paid'
                                && empty($invoice->arca_cae)
                                && ! $arcaAprobado
                            )
                                <form
                                    method="POST"
                                    action="{{ route('admin.invoices.retry-arca', $invoice) }}"
                                >
                                    @csrf

                                    <button type="submit" class="btn">
                                        Reintentar {{ $esExportacion ? 'WSFEX' : 'ARCA' }}
                                    </button>
                                </form>
                            @endif

                            @if (
                                $invoice->payment_status !== 'paid'
                                && $invoice->amount_paid === null
                                && empty($invoice->mercadopago_payment_id)
                                && empty($invoice->arca_cae)
                                && ! $arcaAprobado
                            )
                                <form
                                    method="POST"
                                    action="{{ route('admin.invoices.destroy', $invoice) }}"
                                    onsubmit="return confirm('¿Eliminar esta factura? Esta acción no se puede deshacer.');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                            @endif

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>