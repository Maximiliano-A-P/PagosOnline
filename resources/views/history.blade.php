<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Historial de facturas
        </h2>
    </x-slot>

    <div class="py-12">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- ================================================== --}}
                {{-- TÍTULO --}}
                {{-- ================================================== --}}

                <h3 class="text-lg font-semibold text-gray-900">
                    Historial de facturas
                </h3>

                <p class="mt-1 text-sm text-gray-600">
                    Consulte todas las facturas emitidas para los documentos
                    agregados a su cuenta.
                </p>


                {{-- ================================================== --}}
                {{-- TABLA --}}
                {{-- ================================================== --}}

                @if($invoices->count())

                    <div class="mt-6 overflow-x-auto">

                        <table class="min-w-full divide-y divide-gray-200">

                            <thead>
                                <tr>

                                    <th
                                        class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Documento
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Cliente
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Servicio
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Emisión
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Vencimiento
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Importe
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Estado
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right text-xs
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Acciones
                                    </th>

                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">

                                @foreach($invoices as $invoice)

                                    <tr>

                                        <td
                                            class="px-4 py-4 text-sm
                                                   text-gray-900"
                                        >
                                            {{ $invoice->client_document }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-sm
                                                   text-gray-900"
                                        >
                                            {{ $invoice->client_name }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-sm
                                                   text-gray-900"
                                        >
                                            {{ $invoice->service_name }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-sm
                                                   text-gray-600"
                                        >
                                            {{ $invoice->issued_at }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-sm
                                                   text-gray-600"
                                        >
                                            {{ $invoice->due_date }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-sm
                                                   font-medium text-gray-900"
                                        >
                                            ${{ number_format(
                                                $invoice->price,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </td>

                                        <td class="px-4 py-4 text-sm">

                                            @if($invoice->payment_status === 'paid')

                                                <span
                                                    class="inline-flex items-center
                                                           rounded-full
                                                           bg-green-100
                                                           px-3 py-1
                                                           text-xs
                                                           font-semibold
                                                           text-green-800"
                                                >
                                                    Pagada
                                                </span>

                                            @else

                                                <span
                                                    class="inline-flex items-center
                                                           rounded-full
                                                           bg-yellow-100
                                                           px-3 py-1
                                                           text-xs
                                                           font-semibold
                                                           text-yellow-800"
                                                >
                                                    Pendiente
                                                </span>

                                            @endif

                                        </td>

                                        <td
                                            class="px-4 py-4 text-right"
                                        >

                                            <div
                                                class="flex justify-end
                                                       gap-2"
                                            >

                                                @if($invoice->payment_status !== 'paid')

                                                    <a
                                                        href="{{ route(
                                                            'dashboard.invoices.pay',
                                                            $invoice
                                                        ) }}"
                                                        class="inline-flex
                                                               items-center
                                                               px-3 py-2
                                                               bg-gray-800
                                                               rounded-md
                                                               text-xs
                                                               font-semibold
                                                               text-white
                                                               uppercase
                                                               tracking-widest
                                                               hover:bg-gray-700"
                                                    >
                                                        Pagar
                                                    </a>

                                                @endif

                                                <a
                                                    href="{{ route(
                                                        'dashboard.invoices.pdf',
                                                        $invoice
                                                    ) }}"
                                                    class="inline-flex
                                                           items-center
                                                           px-3 py-2
                                                           border
                                                           border-gray-300
                                                           rounded-md
                                                           text-xs
                                                           font-semibold
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


                    {{-- ================================================== --}}
                    {{-- PAGINACIÓN --}}
                    {{-- ================================================== --}}

                    <div class="mt-6">

                        {{ $invoices->links() }}

                    </div>

                @else

                    <p class="mt-6 text-sm text-gray-600">
                        No hay facturas para los documentos agregados.
                    </p>

                @endif


                {{-- ================================================== --}}
                {{-- VOLVER --}}
                {{-- ================================================== --}}

                <div class="mt-6">

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center
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
                        Volver al dashboard
                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
