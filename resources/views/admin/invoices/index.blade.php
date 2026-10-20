<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="font-semibold text-white leading-tight text-[32px]">
                Facturas
            </h2>

            <div class="flex items-center gap-4 flex-wrap">
                <a href="{{ route('admin.dashboard') }}" class="btn">
                    Volver al panel
                </a>

                <button type="button" id="batch-open" class="btn" disabled>
                    Generar por lote
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $nombresComprobante = [
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
    @endphp

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
            color: #fff;
            font-size: 17px;
            line-height: normal;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            transition: background-color .15s ease-in-out;
        }

        .btn:hover {
            background-color: #374151;
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #4b5563;
        }

        .btn-secondary {
            background-color: #fff;
            border-color: #9ca3af;
            color: #111827;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6;
        }

        .btn-danger {
            background-color: #991b1b;
            border-color: #991b1b;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .invoice-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .summary-label {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
        }

        .summary-value {
            margin-top: 8px;
            font-size: 30px;
            font-weight: 700;
            color: #111827;
        }

        .invoice-card {
            padding: 24px;
            margin-bottom: 14px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }

        .invoice-card-content {
            display: flex;
            justify-content: space-between;
            gap: 28px;
        }

        .invoice-data {
            flex: 1;
            min-width: 0;
        }

        .invoice-info,
        .invoice-amounts {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .invoice-amounts {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .invoice-field {
            min-width: 0;
        }

        .invoice-label {
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 15px;
            font-weight: 600;
        }

        .invoice-value {
            color: #111827;
            font-size: 18px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .invoice-value-normal {
            color: #111827;
            font-size: 17px;
            overflow-wrap: anywhere;
        }

        .invoice-actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            min-width: 155px;
            padding-left: 22px;
            border-left: 1px solid #e5e7eb;
        }

        .invoice-actions .btn,
        .invoice-actions form {
            width: 100%;
        }

        .invoice-actions form {
            margin: 0;
        }

        .invoice-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 700;
            color: white;
        }

        .status-paid,
        .status-approved {
            background: #15803d;
        }

        .status-pending {
            background: #374151;
        }

        .status-error {
            background: #b91c1c;
        }

        .status-neutral {
            background: #6b7280;
        }

        .retention-notice {
            display: inline-block;
            margin-top: 10px;
            padding: 7px 10px;
            border: 1px solid #d97706;
            border-radius: 6px;
            background: #fffbeb;
            color: #92400e;
            font-size: 14px;
            font-weight: 700;
        }

        .export-notice {
            display: inline-block;
            margin-top: 10px;
            padding: 7px 10px;
            border: 1px solid #93c5fd;
            border-radius: 6px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 14px;
            font-weight: 700;
        }

        .invoice-empty {
            padding: 40px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            text-align: center;
            color: #111827;
            font-size: 20px;
        }

        .ac-wrap {
            position: relative;
        }

        .ac-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 30;
            max-height: 340px;
            overflow-y: auto;
            margin: 4px 0 0;
            padding: 0;
            list-style: none;
            background: #fff;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, .15);
        }

        .ac-list[hidden] {
            display: none;
        }

        .ac-item {
            padding: 10px 14px;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
            font-size: 17px;
            cursor: pointer;
        }

        .ac-item:last-child {
            border-bottom: none;
        }

        .ac-item small {
            display: block;
            color: #4b5563;
            font-size: 14px;
        }

        .ac-item.is-active,
        .ac-item:hover {
            background: #f3f4f6;
        }

        .batch-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(17, 24, 39, .6);
        }

        .batch-overlay.is-open {
            display: flex;
        }

        .batch-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 640px;
            max-height: 90vh;
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .25);
        }

        .batch-card-header {
            padding: 20px 24px;
            background: #111827;
        }

        .batch-card-header h3 {
            margin: 0;
            color: #fff;
            font-size: 22px;
            font-weight: 700;
        }

        .batch-card-body {
            padding: 24px;
            overflow-y: auto;
            color: #111827;
            font-size: 17px;
        }

        .batch-card-body p {
            margin: 0 0 14px;
        }

        .batch-list {
            margin: 0;
            padding: 0;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            list-style: none;
        }

        .batch-list li {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 10px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .batch-list li:last-child {
            border-bottom: none;
        }

        .batch-qty {
            font-weight: 700;
            white-space: nowrap;
        }

        .batch-total {
            margin-top: 14px;
            font-weight: 700;
        }

        .batch-error {
            color: #b91c1c;
        }

        .batch-card-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 1100px) {
            .invoice-info,
            .invoice-amounts {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .invoice-card-content {
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .invoice-summary {
                grid-template-columns: 1fr;
            }

            .invoice-card-content {
                flex-direction: column;
            }

            .invoice-info,
            .invoice-amounts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .invoice-actions {
                flex-direction: row;
                flex-wrap: wrap;
                min-width: 0;
                padding-top: 18px;
                padding-left: 0;
                border-top: 1px solid #e5e7eb;
                border-left: none;
            }

            .invoice-actions .btn,
            .invoice-actions form {
                width: auto;
            }
        }

        @media (max-width: 500px) {
            .invoice-info,
            .invoice-amounts {
                grid-template-columns: 1fr;
            }

            .invoice-actions {
                flex-direction: column;
            }

            .invoice-actions .btn,
            .invoice-actions form {
                width: 100%;
            }
        }
    </style>

    <div class="py-12">
        <div class="mx-auto" style="width: 90vw; max-width: 1800px;">

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

            <div class="mb-8">
                <h3 class="font-semibold text-white text-[30px]">
                    Facturas registradas
                </h3>
            </div>

            {{-- Filtros --}}
            <div class="mb-8 bg-white border border-gray-300 rounded-lg shadow-sm">
                <div class="p-6">
                    <form
                        method="GET"
                        action="{{ route('admin.invoices.index') }}"
                        class="grid grid-cols-1 md:grid-cols-4 gap-6"
                    >
                        <div>
                            <label for="search" class="block font-semibold text-gray-900">
                                Buscar
                            </label>

                            <div class="ac-wrap">
                                <input
                                    id="search"
                                    name="search"
                                    type="text"
                                    value="{{ request('search') }}"
                                    placeholder="Cliente, documento o servicio"
                                    autocomplete="off"
                                    class="mt-2 block w-full rounded-md border-gray-400 bg-white text-gray-900 text-lg shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                                >

                                <ul id="searchList" class="ac-list" hidden></ul>
                            </div>
                        </div>

                        <div>
                            <label for="payment_status" class="block font-semibold text-gray-900">
                                Estado del pago
                            </label>

                            <select
                                id="payment_status"
                                name="payment_status"
                                class="mt-2 block w-full rounded-md border-gray-400 bg-white text-gray-900 text-lg shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >
                                <option value="">Todos</option>
                                <option value="pending" @selected(request('payment_status') === 'pending')>
                                    Pendientes
                                </option>
                                <option value="paid" @selected(request('payment_status') === 'paid')>
                                    Pagadas
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="date_from" class="block font-semibold text-gray-900">
                                Emisión desde
                            </label>

                            <input
                                id="date_from"
                                name="date_from"
                                type="date"
                                value="{{ request('date_from') }}"
                                class="mt-2 block w-full rounded-md border-gray-400 bg-white text-gray-900 text-lg shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >
                        </div>

                        <div>
                            <label for="date_to" class="block font-semibold text-gray-900">
                                Emisión hasta
                            </label>

                            <input
                                id="date_to"
                                name="date_to"
                                type="date"
                                value="{{ request('date_to') }}"
                                class="mt-2 block w-full rounded-md border-gray-400 bg-white text-gray-900 text-lg shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >
                        </div>

                        <div class="flex items-end gap-3 flex-wrap">
                            <button type="submit" class="btn">
                                Buscar
                            </button>

                            <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Resumen global de deuda --}}
            <div class="invoice-summary bg-white border border-gray-300 rounded-lg shadow-sm">
                <div>
                    <div class="summary-label">
                        Facturas pendientes de pago
                    </div>

                    <div class="summary-value">
                        {{ number_format($resumen['facturas'], 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="summary-label">
                        Clientes deudores
                    </div>

                    <div class="summary-value">
                        {{ number_format($resumen['clientes'], 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="summary-label">
                        Importe pendiente
                    </div>

                    <div class="summary-value">
                        ${{ number_format($resumen['importe'], 2, ',', '.') }}
                    </div>
                </div>
            </div>

            {{-- Listado --}}
            <div class="space-y-4">
                @forelse ($invoices as $invoice)
                    @php
                        $taxPercentage = (float) ($invoice->tax_percentage ?? 0);
                        $price = (float) $invoice->price;
                        $overduePrice = (float) $invoice->overdue_price;

                        $priceWithTax = round(
                            $price * (1 + ($taxPercentage / 100)),
                            2
                        );

                        $overduePriceWithTax = round(
                            $overduePrice * (1 + ($taxPercentage / 100)),
                            2
                        );

                        $tipoComprobante = (int) ($invoice->arca_invoice_type ?? 0);

                        $esExportacion = in_array(
                            $tipoComprobante,
                            [19, 20, 21],
                            true
                        ) || (int) ($invoice->client_iva_condition ?? 0) === 9;

                        $esAConLeyendaRetencion = in_array(
                            $tipoComprobante,
                            [51, 52, 53],
                            true
                        );

                        $nombreComprobante = $nombresComprobante[$tipoComprobante]
                            ?? ($esExportacion ? 'Factura E' : null);

                        $estadoArca = $invoice->arca_status ?: 'Sin procesar';

                        $arcaAprobado = strtolower(
                            (string) $invoice->arca_status
                        ) === 'aprobado';

                        $arcaError = in_array(
                            strtolower((string) $invoice->arca_status),
                            ['rechazado', 'error', 'fallido'],
                            true
                        );

                        $identificadorFiscalExtranjero =
                            $invoice->client_arca_cuit_pais_cliente
                            ?: $invoice->client_arca_id_impositivo;

                        $numeroComprobante = null;

                        if (
                            $invoice->arca_point_of_sale
                            && $invoice->arca_invoice_number
                        ) {
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

                    <article class="invoice-card">
                        <div class="invoice-card-content">
                            <div class="invoice-data">

                                <div class="invoice-info">
                                    <div class="invoice-field">
                                        <div class="invoice-label">Cliente</div>

                                        <div class="invoice-value">
                                            {{ $invoice->client_name ?? 'N/A' }}
                                        </div>

                                        <div class="invoice-value-normal">
                                            @if ($esExportacion)
                                                {{ $identificadorFiscalExtranjero ?: 'Sin identificador fiscal' }}
                                            @else
                                                Documento: {{ $invoice->client_document ?? 'N/A' }}
                                            @endif
                                        </div>
                                    </div>

                                    <div class="invoice-field">
                                        <div class="invoice-label">Servicio</div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->service_name ?? 'N/A' }}
                                        </div>
                                    </div>

                                    <div class="invoice-field">
                                        <div class="invoice-label">Emisión</div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->issued_at?->format('d/m/Y') ?? 'N/A' }}
                                        </div>
                                    </div>

                                    <div class="invoice-field">
                                        <div class="invoice-label">Vencimiento</div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="invoice-info mt-6">
                                    <div class="invoice-field">
                                        <div class="invoice-label">Estado del pago</div>

                                        @if ($invoice->payment_status === 'paid')
                                            <span class="invoice-status status-paid">
                                                Pagada
                                            </span>
                                        @else
                                            <span class="invoice-status status-pending">
                                                Pendiente
                                            </span>
                                        @endif
                                    </div>

                                    <div class="invoice-field">
                                        <div class="invoice-label">Estado ARCA</div>

                                        @if ($arcaAprobado)
                                            <span class="invoice-status status-approved">
                                                {{ $estadoArca }}
                                            </span>
                                        @elseif ($arcaError)
                                            <span class="invoice-status status-error">
                                                {{ $estadoArca }}
                                            </span>
                                        @else
                                            <span class="invoice-status status-neutral">
                                                {{ $estadoArca }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="invoice-field">
                                        <div class="invoice-label">Tipo de comprobante</div>

                                        <div class="invoice-value-normal">
                                            @if ($nombreComprobante)
                                                {{ $nombreComprobante }}
                                                <span class="text-gray-500">
                                                    ({{ $tipoComprobante }})
                                                </span>
                                            @elseif ($esExportacion)
                                                Factura E — pendiente de emisión
                                            @else
                                                Sin emitir
                                            @endif
                                        </div>

                                        @if ($esExportacion)
                                            <div class="export-notice">
                                                Exportación
                                            </div>
                                        @endif

                                        @if ($esAConLeyendaRetencion)
                                            <div class="retention-notice">
                                                OPERACIÓN SUJETA A RETENCIÓN
                                            </div>
                                        @endif
                                    </div>

                                    <div class="invoice-field">
                                        <div class="invoice-label">Comprobante ARCA</div>

                                        <div class="invoice-value-normal">
                                            {{ $numeroComprobante ?? 'Sin número' }}
                                        </div>

                                        <div class="invoice-label mt-2">CAE</div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->arca_cae ?: '—' }}
                                        </div>
                                    </div>
                                </div>

                                @if ($esExportacion)
                                    <div class="invoice-amounts">
                                        <div class="invoice-field">
                                            <div class="invoice-label">Importe</div>

                                            <div class="invoice-value">
                                                ${{ number_format($price, 2, ',', '.') }}
                                            </div>
                                        </div>

                                        <div class="invoice-field">
                                            <div class="invoice-label">Importe vencido</div>

                                            <div class="invoice-value">
                                                ${{ number_format($overduePrice, 2, ',', '.') }}
                                            </div>
                                        </div>

                                        <div class="invoice-field">
                                            <div class="invoice-label">Importe pagado</div>

                                            <div class="invoice-value">
                                                @if ($invoice->amount_paid !== null)
                                                    ${{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </div>

                                        <div class="invoice-field">
                                            <div class="invoice-label">País de destino</div>

                                            <div class="invoice-value-normal">
                                                {{ $invoice->client_arca_destination_country ?: '—' }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="invoice-amounts">
                                        <div class="invoice-field">
                                            <div class="invoice-label">Precio neto</div>

                                            <div class="invoice-value">
                                                ${{ number_format($price, 2, ',', '.') }}
                                            </div>
                                        </div>

                                        <div class="invoice-field">
                                            <div class="invoice-label">Precio vencido neto</div>

                                            <div class="invoice-value">
                                                ${{ number_format($overduePrice, 2, ',', '.') }}
                                            </div>
                                        </div>

                                        <div class="invoice-field">
                                            <div class="invoice-label">Impuestos</div>

                                            <div class="invoice-value">
                                                {{ number_format($taxPercentage, 2, ',', '.') }}%
                                            </div>
                                        </div>

                                        <div class="invoice-field">
                                            <div class="invoice-label">Precio con impuestos</div>

                                            <div class="invoice-value">
                                                ${{ number_format($priceWithTax, 2, ',', '.') }}
                                            </div>

                                            <div class="invoice-label mt-2">Vencido con impuestos</div>

                                            <div class="invoice-value-normal">
                                                ${{ number_format($overduePriceWithTax, 2, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="invoice-actions">
                                <a
                                    href="{{ route('admin.invoices.show', $invoice) }}"
                                    class="btn"
                                >
                                    Ver detalle
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
                    </article>
                @empty
                    <div class="invoice-empty">
                        No hay facturas registradas con esos filtros.
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>

    {{-- Confirmación para generar facturas por lote --}}
    <div
        id="batch-overlay"
        class="batch-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="batch-title"
    >
        <div class="batch-card">
            <div class="batch-card-header">
                <h3 id="batch-title">Generar facturas por lote</h3>
            </div>

            <div class="batch-card-body" id="batch-body"></div>

            <div class="batch-card-footer">
                <form
                    id="batch-form"
                    method="POST"
                    action="{{ route('admin.invoices.generate') }}"
                    style="margin: 0;"
                >
                    @csrf

                    <button
                        type="submit"
                        id="batch-accept"
                        class="btn"
                        disabled
                    >
                        Aceptar
                    </button>
                </form>

                <button
                    type="button"
                    id="batch-reject"
                    class="btn btn-secondary"
                >
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openButton = document.getElementById('batch-open');
            const overlay = document.getElementById('batch-overlay');
            const body = document.getElementById('batch-body');
            const form = document.getElementById('batch-form');
            const accept = document.getElementById('batch-accept');
            const reject = document.getElementById('batch-reject');

            const previewUrl = @json(route('admin.invoices.generate.preview'));

            const state = {
                loading: false,
                submitting: false,
            };

            function close() {
                if (state.submitting) {
                    return;
                }

                overlay.classList.remove('is-open');
            }

            function showMessage(message, isError = false) {
                body.innerHTML = '';

                const paragraph = document.createElement('p');
                paragraph.textContent = message;

                if (isError) {
                    paragraph.className = 'batch-error';
                }

                body.appendChild(paragraph);
            }

            function renderPreview(data) {
                body.innerHTML = '';

                if (!data.total) {
                    showMessage(
                        'No hay facturas para generar en este momento.'
                    );

                    accept.disabled = true;
                    return;
                }

                const intro = document.createElement('p');
                intro.textContent = 'Se generarán facturas de los siguientes servicios:';
                body.appendChild(intro);

                const list = document.createElement('ul');
                list.className = 'batch-list';

                data.services.forEach(function (item) {
                    const row = document.createElement('li');

                    const name = document.createElement('span');
                    name.textContent = item.service;

                    const quantity = document.createElement('span');
                    quantity.className = 'batch-qty';
                    quantity.textContent = item.count;

                    row.appendChild(name);
                    row.appendChild(quantity);
                    list.appendChild(row);
                });

                body.appendChild(list);

                const total = document.createElement('div');
                total.className = 'batch-total';
                total.textContent = 'Total: ' + data.total + ' factura(s)';
                body.appendChild(total);

                const note = document.createElement('p');
                note.style.marginTop = '14px';
                note.textContent = 'Se utilizarán los precios actuales de los servicios.';
                body.appendChild(note);

                accept.disabled = false;
            }

            async function openCard() {
                if (state.loading || state.submitting) {
                    return;
                }

                state.loading = true;
                openButton.disabled = true;
                accept.disabled = true;

                showMessage('Calculando las facturas que se generarían…');
                overlay.classList.add('is-open');

                try {
                    const response = await fetch(previewUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo obtener la vista previa.');
                    }

                    const data = await response.json();
                    renderPreview(data);
                } catch (error) {
                    showMessage(
                        'No se pudo calcular el lote. Cerrá esta ventana e intentá nuevamente.',
                        true
                    );
                } finally {
                    state.loading = false;
                    openButton.disabled = false;
                }
            }

            openButton.addEventListener('click', openCard);
            reject.addEventListener('click', close);

            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    close();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    close();
                }
            });

            form.addEventListener('submit', function (event) {
                if (state.submitting) {
                    event.preventDefault();
                    return;
                }

                state.submitting = true;
                accept.disabled = true;
                reject.disabled = true;
                accept.textContent = 'Generando…';
            });

            openButton.disabled = false;
        });
    </script>

    <script src="{{ asset('js/search-dropdown.js') }}"></script>

    <script>
        searchDropdown({
            inputId: 'search',
            listId: 'searchList',
            sources: [
                {
                    url: @json(route('admin.invoices.generate-manual.clients')),
                    value: function (client) {
                        return client.name;
                    },
                    render: function (client, item) {
                        item.textContent = client.name + ' — Documento ' + client.document;

                        const small = document.createElement('small');
                        small.textContent = 'Cliente';
                        item.appendChild(small);
                    },
                },
                {
                    url: @json(route('admin.invoices.generate-manual.services')),
                    value: function (service) {
                        return service.name;
                    },
                    render: function (service, item) {
                        item.textContent = service.name;

                        const small = document.createElement('small');
                        small.textContent = 'Servicio';
                        item.appendChild(small);
                    },
                },
            ],
        });
    </script>
</x-app-layout>