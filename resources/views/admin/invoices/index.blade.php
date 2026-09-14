<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <h2 class="font-semibold text-white leading-tight text-[4vh]">
                Facturas
            </h2>

            <div class="header-actions flex items-center gap-4">

                {{-- Cargar factura histórica --}}
                <a
                    href="{{ route('admin.invoices.create') }}"
                    class="btn"
                >
                    Nueva factura manual
                </a>

                {{-- Generar facturas periódicas --}}
                <form
                    method="POST"
                    action="{{ route('admin.invoices.generate') }}"
                    onsubmit="return confirm(
                        '¿Generar las facturas correspondientes? Se utilizarán los precios actuales de los servicios.'
                    );"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn"
                    >
                        Generar facturas
                    </button>

                </form>

            </div>

        </div>

    </x-slot>


    <style>

        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            background-color: #111827;
            border: 1px solid #111827;
            border-radius: 6px;
            font-weight: 600;
            color: #ffffff;
            font-size: 14px;
            line-height: normal;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            margin: 0;
            transition: background-color 0.15s ease-in-out;
        }

        .btn:hover {
            background-color: #374151;
        }

        .btn-secondary {
            background-color: #ffffff;
            border: 1px solid #9ca3af;
            color: #111827;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6;
        }

        .btn-success {
            background-color: #15803d;
            border-color: #15803d;
        }

        .btn-success:hover {
            background-color: #166534;
        }

        /*
         * ==========================================================
         * TARJETAS DE FACTURAS
         * ==========================================================
         */

        .header-actions .btn {
            width: 220px;
            height: 50px;
            box-sizing: border-box;
            font-size: 17px;
        }

        .invoice-list,
        .invoice-filters {
            width: 90%;
            margin: 0 auto 40px auto;
        }

        .invoice-card {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            padding: 24px;
            margin-bottom: 10px;
        }

        .invoice-card-content {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 30px;
        }

        .invoice-data {
            flex: 1;
            min-width: 0;
        }

        .invoice-info {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 20px;
        }

        .invoice-amounts {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 20px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .invoice-field {
            min-width: 0;
        }

        .invoice-label {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .invoice-value {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .invoice-value-normal {
            font-size: 16px;
            font-weight: 500;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .invoice-actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            min-width: 160px;
            border-left: 1px solid #e5e7eb;
            padding-left: 25px;
        }

        .invoice-actions .btn,
        .invoice-actions form {
            width: 100%;
        }

        .invoice-actions form {
            margin: 0;
        }

        .invoice-filters input,
        .invoice-filters select {
            height: 48px;
            box-sizing: border-box;
        }

        .invoice-filters .btn {
            width: 160px;
            height: 42px;
            box-sizing: border-box;
        }

        .invoice-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 13px;
            color: #ffffff;
        }

        .invoice-status-paid {
            background-color: #15803d;
        }

        .invoice-status-pending {
            background-color: #111827;
        }

        .invoice-empty {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            color: #111827;
            font-size: 20px;
        }

        @media (max-width: 1100px) {

            .invoice-info {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .invoice-amounts {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

        }

        @media (max-width: 768px) {

            .invoice-list {
                width: 100%;
            }

            .invoice-card {
                padding: 18px;
            }

            .invoice-card-content {
                flex-direction: column;
                gap: 20px;
            }

            .invoice-info {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .invoice-amounts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .invoice-actions {
                border-left: none;
                border-top: 1px solid #e5e7eb;
                padding-left: 0;
                padding-top: 20px;
                flex-direction: row;
                flex-wrap: wrap;
                min-width: auto;
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

        }

    </style>


    <div class="py-12">

        <div class="w-full px-6 lg:px-8">


            {{-- ================================================== --}}
            {{-- Mensaje de éxito --}}
            {{-- ================================================== --}}

            @if (session('success'))

                <div
                    class="mb-8 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[3vh]"
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- ================================================== --}}
            {{-- Mensaje de error --}}
            {{-- ================================================== --}}

            @if (session('error'))

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm text-[3vh]"
                >
                    {{ session('error') }}
                </div>

            @endif


            {{-- ================================================== --}}
            {{-- Encabezado --}}
            {{-- ================================================== --}}

            <div class="mb-8 max-w-7xl mx-auto">

                <h3 class="font-semibold text-white text-[4vh]">
                    Facturas registradas
                </h3>

            </div>


            {{-- ================================================== --}}
            {{-- Búsqueda y filtros --}}
            {{-- ================================================== --}}

            <div
                class="invoice-filters bg-white border border-gray-300 rounded-lg
                    shadow-sm"
            >

                <div class="p-6">

                    <form
                        method="GET"
                        action="{{ route('admin.invoices.index') }}"
                        class="grid grid-cols-1 md:grid-cols-4 gap-6"
                    >

                        {{-- Búsqueda --}}
                        <div>

                            <label
                                for="search"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Buscar
                            </label>

                            <input
                                id="search"
                                name="search"
                                type="text"
                                value="{{ request('search') }}"
                                placeholder="Cliente, documento o servicio"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                        </div>


                        {{-- Estado --}}
                        <div>

                            <label
                                for="payment_status"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Estado
                            </label>

                            <select
                                id="payment_status"
                                name="payment_status"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                                <option value="">
                                    Todos
                                </option>

                                <option
                                    value="pending"
                                    @selected(request('payment_status') === 'pending')
                                >
                                    Pendientes
                                </option>

                                <option
                                    value="paid"
                                    @selected(request('payment_status') === 'paid')
                                >
                                    Pagadas
                                </option>

                            </select>

                        </div>


                        {{-- Buscar --}}
                        <div class="flex items-end">

                            <button
                                type="submit"
                                class="btn"
                            >
                                Buscar
                            </button>

                        </div>


                        {{-- Limpiar --}}
                        <div class="flex items-end">

                            <a
                                href="{{ route('admin.invoices.index') }}"
                                class="btn"
                            >
                                Limpiar
                            </a>

                        </div>

                    </form>

                </div>

            </div>


            {{-- ================================================== --}}
            {{-- Listado de facturas --}}
            {{-- ================================================== --}}

            <div class="invoice-list">

                @forelse ($invoices as $invoice)

                    @php

                        $taxPercentage =
                            $invoice->tax_percentage ?? 0;

                        $price =
                            (float) $invoice->price;

                        $overduePrice =
                            (float) $invoice->overdue_price;

                        $priceWithTax =
                            round(
                                $price * (1 + ($taxPercentage / 100)),
                                2
                            );

                        $overduePriceWithTax =
                            round(
                                $overduePrice * (1 + ($taxPercentage / 100)),
                                2
                            );

                    @endphp


                    {{-- ================================================== --}}
                    {{-- TARJETA --}}
                    {{-- ================================================== --}}

                    <div class="invoice-card">

                        <div class="invoice-card-content">


                            {{-- ================================================== --}}
                            {{-- DATOS --}}
                            {{-- ================================================== --}}

                            <div class="invoice-data">


                                {{-- ================================================== --}}
                                {{-- INFORMACIÓN --}}
                                {{-- ================================================== --}}

                                <div class="invoice-info">

                                    {{-- Cliente --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Cliente
                                        </div>

                                        <div class="invoice-value">
                                            {{ $invoice->client_name ?? 'N/A' }}
                                        </div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->client_document ?? 'N/A' }}
                                        </div>

                                    </div>


                                    {{-- Servicio --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Servicio
                                        </div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->service_name ?? 'N/A' }}
                                        </div>

                                    </div>


                                    {{-- Emisión --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Emisión
                                        </div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->issued_at?->format('d/m/Y') ?? 'N/A' }}
                                        </div>

                                    </div>


                                    {{-- Vencimiento --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Vencimiento
                                        </div>

                                        <div class="invoice-value-normal">
                                            {{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}
                                        </div>

                                    </div>


                                    {{-- Estado --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Estado
                                        </div>

                                        @if ($invoice->payment_status === 'paid')

                                            <span
                                                class="invoice-status invoice-status-paid"
                                            >
                                                Pagada
                                            </span>

                                        @else

                                            <span
                                                class="invoice-status invoice-status-pending"
                                            >
                                                Pendiente
                                            </span>

                                        @endif

                                    </div>

                                </div>


                                {{-- ================================================== --}}
                                {{-- IMPORTES --}}
                                {{-- ================================================== --}}

                                <div class="invoice-amounts">

                                    {{-- Precio neto --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Precio (NETO)
                                        </div>

                                        <div class="invoice-value">
                                            ${{ number_format(
                                                $price,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </div>

                                    </div>


                                    {{-- Precio vencido neto --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Precio vencido (NETO)
                                        </div>

                                        <div class="invoice-value">
                                            ${{ number_format(
                                                $overduePrice,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </div>

                                    </div>


                                    {{-- Impuestos --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Impuestos %
                                        </div>

                                        <div class="invoice-value">
                                            {{ number_format(
                                                $taxPercentage,
                                                2,
                                                ',',
                                                '.'
                                            ) }}%
                                        </div>

                                    </div>


                                    {{-- Neto + impuestos --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Neto + Impuestos
                                        </div>

                                        <div class="invoice-value">
                                            ${{ number_format(
                                                $priceWithTax,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </div>

                                    </div>


                                    {{-- Vencido + impuestos --}}
                                    <div class="invoice-field">

                                        <div class="invoice-label">
                                            Vencido + Impuestos
                                        </div>

                                        <div class="invoice-value">
                                            ${{ number_format(
                                                $overduePriceWithTax,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </div>

                                    </div>

                                </div>


                            </div>


                            {{-- ================================================== --}}
                            {{-- ACCIONES --}}
                            {{-- ================================================== --}}

                            <div class="invoice-actions">

                                <a
                                    href="{{ route(
                                        'admin.invoices.show',
                                        $invoice
                                    ) }}"
                                    class="btn"
                                >
                                    Ver
                                </a>


                                @if ($invoice->payment_status !== 'paid')

                                    <a
                                        href="{{ route(
                                            'admin.invoices.payment',
                                            $invoice
                                        ) }}"
                                        class="btn"
                                    >
                                        Registrar pago
                                    </a>

                                @endif


                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.invoices.destroy',
                                        $invoice
                                    ) }}"
                                    onsubmit="return confirm(
                                        '¿Eliminar esta factura? Esta acción no se puede deshacer.'
                                    );"
                                >
                                    @csrf

                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn"
                                    >
                                        Eliminar
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="invoice-empty">
                        No hay facturas registradas.
                    </div>

                @endforelse

            </div>


            {{-- ================================================== --}}
            {{-- Paginación --}}
            {{-- ================================================== --}}

            <div class="mt-8">

                {{ $invoices->links() }}

            </div>

        </div>

    </div>

</x-app-layout>