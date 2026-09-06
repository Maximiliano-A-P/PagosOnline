<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- ============================================= --}}
            {{-- AGREGAR DOCUMENTO --}}
            {{-- ============================================= --}}

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

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
                        class="inline-flex items-center px-4 py-2
                               bg-gray-800 border border-transparent
                               rounded-md font-semibold text-xs text-white
                               uppercase tracking-widest
                               hover:bg-gray-700"
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

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

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

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <h3 class="text-lg font-semibold text-gray-900">
                    Facturas pendientes
                </h3>

                @if(isset($pendingInvoices) && $pendingInvoices->count())

                    <div class="mt-4 overflow-x-auto">

                        <table class="min-w-full divide-y divide-gray-200">

                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase">
                                        Documento
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase">
                                        Servicio
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase">
                                        Emisión
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase">
                                        Vencimiento
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase">
                                        Importe
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs
                                               font-medium text-gray-500
                                               uppercase">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">

                                @foreach($pendingInvoices as $invoice)

                                    <tr>

                                        <td class="px-4 py-4 text-sm text-gray-900">
                                            {{ $invoice->client_document }}
                                        </td>

                                        <td class="px-4 py-4 text-sm text-gray-900">
                                            {{ $invoice->service_name }}
                                        </td>

                                        <td class="px-4 py-4 text-sm text-gray-600">
                                            {{ $invoice->issued_at }}
                                        </td>

                                        <td class="px-4 py-4 text-sm text-gray-600">
                                            {{ $invoice->due_date }}
                                        </td>

                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                            ${{ number_format(
                                                $invoice->price,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td class="px-4 py-4 text-right">

                                            <div class="flex justify-end gap-2">

                                                {{-- PAGO ONLINE --}}
                                                <a
                                                    href="{{ route(
                                                        'dashboard.invoices.pay',
                                                        $invoice
                                                    ) }}"
                                                    class="inline-flex items-center
                                                           px-3 py-2
                                                           bg-gray-800
                                                           rounded-md
                                                           text-xs font-semibold
                                                           text-white
                                                           uppercase
                                                           tracking-widest
                                                           hover:bg-gray-700"
                                                >
                                                    Pagar
                                                </a>

                                                {{-- PDF --}}
                                                <a
                                                    href="{{ route(
                                                        'dashboard.invoices.pdf',
                                                        $invoice
                                                    ) }}"
                                                    class="inline-flex items-center
                                                           px-3 py-2
                                                           border
                                                           border-gray-300
                                                           rounded-md
                                                           text-xs font-semibold
                                                           text-gray-700
                                                           uppercase
                                                           tracking-widest
                                                           hover:bg-gray-50"
                                                >
                                                    Descargar PDF
                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <p class="mt-4 text-sm text-gray-600">
                        No hay facturas pendientes para los documentos agregados.
                    </p>

                @endif

            </div>


            {{-- ============================================= --}}
            {{-- HISTORIAL --}}
            {{-- ============================================= --}}

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <h3 class="text-lg font-semibold text-gray-900">
                    Historial de facturas
                </h3>

                <p class="mt-1 text-sm text-gray-600">
                    Consulte todas las facturas emitidas para los documentos
                    agregados.
                </p>

                <a
                    href="{{ route('dashboard.invoices.history') }}"
                    class="mt-4 inline-flex items-center
                           px-4 py-2
                           bg-gray-800
                           border border-transparent
                           rounded-md
                           font-semibold
                           text-xs
                           text-white
                           uppercase
                           tracking-widest
                           hover:bg-gray-700"
                >
                    Ver historial de facturas
                </a>

            </div>

        </div>
    </div>

</x-app-layout>