<x-app-layout>

<x-slot name="header">

    <div>
        <h2 class="font-semibold text-white leading-tight text-[4vh]">
            Bienvenido
        </h2>
    </div>

</x-slot>


<style>

    /*
     * ==========================================================
     * CONTENEDOR GENERAL
     * ==========================================================
     */

    .dashboard-container {
        width: 90vw;
        margin-left: auto;
        margin-right: auto;
        box-sizing: border-box;
    }


    /*
     * ==========================================================
     * TAMAÑO BASE DE TEXTO
     * ==========================================================
     *
     * El tamaño de referencia es 21px.
     *
     * Se aplica solamente a los textos que estaban
     * por debajo de este tamaño.
     */

    .dashboard-page p,
    .dashboard-page label,
    .dashboard-page input,
    .dashboard-page button,
    .dashboard-page a,
    .dashboard-page span,
    .dashboard-page th,
    .dashboard-page td,
    .dashboard-page .dashboard-text {
        font-size: 21px;
    }


    /*
     * ==========================================================
     * BOTONES
     * ==========================================================
     */

    .dashboard-btn {
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 42px;
        padding: 6px 14px;
        border-radius: 6px;
        font-weight: 600;
        color: #ffffff;
        font-size: 21px;
        line-height: normal;
        font-family: inherit;
        text-decoration: none;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        margin: 0;
        transition: background-color 0.15s ease-in-out;
    }

    .dashboard-btn-primary {
        background-color: #111827;
        border: 1px solid #111827;
    }

    .dashboard-btn-primary:hover {
        background-color: #374151;
    }

    .dashboard-btn-secondary {
        background-color: #ffffff;
        border: 1px solid #9ca3af;
        color: #111827;
    }

    .dashboard-btn-secondary:hover {
        background-color: #f3f4f6;
    }

    .dashboard-history-btn {
        margin-top: 15px;
    }

    .dashboard-section h3 {
        font-size: 24px;
    }

    /*
     * ==========================================================
     * SECCIONES GENERALES
     * ==========================================================
     */

    .dashboard-section {
        width: 100%;
        margin-left: auto;
        margin-right: auto;
    }


    /*
     * ==========================================================
     * FACTURAS PENDIENTES
     * ==========================================================
     */

    .pending-invoice-list {
        width: 100%;
        margin: 0 auto;
    }

    .pending-invoice-card {
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        padding: 24px;
        margin-bottom: 10px;
    }

    .pending-invoice-card:last-child {
        margin-bottom: 0;
    }

    .pending-invoice-content {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        gap: 30px;
    }

    .pending-invoice-data {
        flex: 1;
        min-width: 0;
    }

    .pending-invoice-info {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 20px;
    }

    .pending-invoice-amounts {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 20px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    .pending-invoice-field {
        min-width: 0;
    }

    .pending-invoice-label {
        font-size: 21px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .pending-invoice-value {
        font-size: 21px;
        font-weight: 600;
        color: #111827;
        overflow-wrap: anywhere;
    }

    .pending-invoice-value-normal {
        font-size: 21px;
        font-weight: 500;
        color: #111827;
        overflow-wrap: anywhere;
    }

    .pending-invoice-actions {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 10px;
        min-width: 160px;
        border-left: 1px solid #e5e7eb;
        padding-left: 25px;
    }

    .pending-invoice-actions .dashboard-btn {
        width: 100%;
    }

    .pending-invoice-empty {
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        color: #111827;
        font-size: 21px;
    }


    /*
     * ==========================================================
     * RESPONSIVE
     * ==========================================================
     */

    @media (max-width: 1100px) {

        .pending-invoice-info {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pending-invoice-amounts {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

    }


    @media (max-width: 768px) {

        .dashboard-container {
            width: 90vw;
        }

        .pending-invoice-card {
            padding: 18px;
        }

        .pending-invoice-content {
            flex-direction: column;
            gap: 20px;
        }

        .pending-invoice-info {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pending-invoice-amounts {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pending-invoice-actions {
            border-left: none;
            border-top: 1px solid #e5e7eb;
            padding-left: 0;
            padding-top: 20px;
            flex-direction: row;
            flex-wrap: wrap;
            min-width: auto;
        }

        .pending-invoice-actions .dashboard-btn {
            width: auto;
            min-width: 160px;
        }

    }


    @media (max-width: 500px) {

        .pending-invoice-info,
        .pending-invoice-amounts {
            grid-template-columns: 1fr;
        }

        .pending-invoice-actions {
            flex-direction: column;
        }

        .pending-invoice-actions .dashboard-btn {
            width: 100%;
        }

    }

</style>


<div class="dashboard-page py-12">

    <div class="dashboard-container">


        {{-- ============================================= --}}
        {{-- AGREGAR DOCUMENTO --}}
        {{-- ============================================= --}}

        <div class="dashboard-section bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

            <h3 class="text-lg font-semibold text-gray-900">
                Agregar documento
            </h3>

            <p class="mt-1 text-sm text-gray-600">
                Ingrese un número de documento para consultar sus facturas.
            </p>

            <form
                method="POST"
                action="{{ route('dashboard.documents.store') }}"
                class="mt-4 flex gap-3"
            >

                @csrf

                <input
                    type="text"
                    name="document"
                    value="{{ old('document') }}"
                    placeholder="Número de documento"
                    required
                    class="block w-full rounded-md border-gray-300 shadow-sm
                           focus:border-indigo-500 focus:ring-indigo-500"
                >

                <button
                    type="submit"
                    class="dashboard-btn dashboard-btn-primary"
                >
                    Agregar
                </button>

            </form>

            @error('document')

                <p class="mt-2 text-sm text-red-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- ============================================= --}}
        {{-- DOCUMENTOS AGREGADOS --}}
        {{-- ============================================= --}}

        <div class="dashboard-section mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

            <h3 class="text-lg font-semibold text-gray-900">
                Documentos Agregados
            </h3>

            @if(empty($documents))

                <p class="mt-4 text-sm text-gray-600">
                    No hay documentos agregados.
                </p>

            @else

                <div class="mt-4 space-y-3">

                    @foreach($documents as $document)

                        <div
                            class="flex items-center justify-between
                                   border border-gray-200 rounded-lg p-4"
                        >

                            <span class="font-medium text-gray-900">
                                {{ $document }}
                            </span>

                            <form
                                method="POST"
                                action="{{ route(
                                    'dashboard.documents.destroy',
                                    $document
                                ) }}"
                            >

                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="text-sm text-red-600
                                           hover:text-red-800"
                                >
                                    Quitar
                                </button>

                            </form>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>


        {{-- ============================================= --}}
        {{-- FACTURAS PENDIENTES --}}
        {{-- ============================================= --}}

        <div class="mt-6">

            <div class="mb-8">

                <h3 class="font-semibold text-white text-[4vh]">
                    Facturas pendientes
                </h3>

            </div>


            @if(isset($pendingInvoices) && $pendingInvoices->count())

                <div class="pending-invoice-list">

                    @foreach($pendingInvoices as $invoice)

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


                        {{-- ================================= --}}
                        {{-- TARJETA --}}
                        {{-- ================================= --}}

                        <div class="pending-invoice-card">

                            <div class="pending-invoice-content">


                                {{-- ================================= --}}
                                {{-- DATOS --}}
                                {{-- ================================= --}}

                                <div class="pending-invoice-data">


                                    {{-- ================================= --}}
                                    {{-- INFORMACIÓN --}}
                                    {{-- ================================= --}}

                                    <div class="pending-invoice-info">


                                        {{-- Cliente --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Cliente
                                            </div>

                                            <div class="pending-invoice-value">
                                                {{ $invoice->client_name ?? 'N/A' }}
                                            </div>

                                            <div class="pending-invoice-value-normal">
                                                {{ $invoice->client_document ?? 'N/A' }}
                                            </div>

                                        </div>


                                        {{-- Servicio --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Servicio
                                            </div>

                                            <div class="pending-invoice-value-normal">
                                                {{ $invoice->service_name ?? 'N/A' }}
                                            </div>

                                        </div>


                                        {{-- Emisión --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Emisión
                                            </div>

                                            <div class="pending-invoice-value-normal">
                                                {{ $invoice->issued_at?->format('d/m/Y') ?? 'N/A' }}
                                            </div>

                                        </div>


                                        {{-- Vencimiento --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Vencimiento
                                            </div>

                                            <div class="pending-invoice-value-normal">
                                                {{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}
                                            </div>

                                        </div>


                                        {{-- Estado --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Estado
                                            </div>

                                            <span
                                                class="inline-flex items-center
                                                       px-3 py-1
                                                       rounded-full
                                                       font-semibold
                                                       text-white
                                                       bg-gray-900"
                                            >
                                                Pendiente
                                            </span>

                                        </div>

                                    </div>


                                    {{-- ================================= --}}
                                    {{-- IMPORTES --}}
                                    {{-- ================================= --}}

                                    <div class="pending-invoice-amounts">


                                        {{-- Precio neto --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Precio (NETO)
                                            </div>

                                            <div class="pending-invoice-value">
                                                ${{ number_format(
                                                    $price,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </div>

                                        </div>


                                        {{-- Precio vencido neto --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Precio vencido (NETO)
                                            </div>

                                            <div class="pending-invoice-value">
                                                ${{ number_format(
                                                    $overduePrice,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </div>

                                        </div>


                                        {{-- Impuestos --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Impuestos %
                                            </div>

                                            <div class="pending-invoice-value">
                                                {{ number_format(
                                                    $taxPercentage,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}%
                                            </div>

                                        </div>


                                        {{-- Neto + impuestos --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Neto + Impuestos
                                            </div>

                                            <div class="pending-invoice-value">
                                                ${{ number_format(
                                                    $priceWithTax,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}
                                            </div>

                                        </div>


                                        {{-- Vencido + impuestos --}}

                                        <div class="pending-invoice-field">

                                            <div class="pending-invoice-label">
                                                Vencido + Impuestos
                                            </div>

                                            <div class="pending-invoice-value">
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


                                {{-- ================================= --}}
                                {{-- ACCIONES --}}
                                {{-- ================================= --}}

                                <div class="pending-invoice-actions">

                                    {{-- PAGO ONLINE --}}

                                    <a
                                        href="{{ route(
                                            'dashboard.invoices.pay',
                                            $invoice
                                        ) }}"
                                        class="dashboard-btn dashboard-btn-primary"
                                    >
                                        Pagar
                                    </a>


                                    {{-- PDF --}}

                                    <a
                                        href="{{ route(
                                            'dashboard.invoices.pdf',
                                            $invoice
                                        ) }}"
                                        class="dashboard-btn dashboard-btn-secondary"
                                    >
                                        Descargar PDF
                                    </a>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="pending-invoice-list">

                    <div class="pending-invoice-empty">
                        No hay facturas pendientes para los documentos agregados.
                    </div>

                </div>

            @endif

        </div>


        {{-- ============================================= --}}
        {{-- HISTORIAL --}}
        {{-- ============================================= --}}

        <div class="dashboard-section mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

            <h3 class="text-lg font-semibold text-gray-900">
                Historial de facturas
            </h3>

            <p class="mt-1 text-sm text-gray-600">
                Consulte todas las facturas emitidas para los documentos
                agregados.
            </p>

            <a
                href="{{ route('dashboard.invoices.history') }}"
                class="dashboard-history-btn dashboard-btn dashboard-btn-primary"
            >
                Ver historial de facturas
            </a>

        </div>

    </div>

</div>

</x-app-layout>