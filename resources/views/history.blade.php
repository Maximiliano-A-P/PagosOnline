<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Historial de facturas
        </h2>
    </x-slot>


    <style>
        /*
         * Medidas tomadas del dashboard general:
         * texto base 21px, botones de 42px de alto,
         * contenedor del 90% del ancho de la pantalla.
         */
        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 21px;
            line-height: normal;
            font-family: inherit;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            margin: 0;
            transition: background-color 0.15s ease-in-out;
        }

        .btn-primary {
            background-color: #111827; /* gray-900 */
            border: 1px solid #111827;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #374151; /* gray-700 */
        }

        .btn-secondary {
            background-color: #ffffff;
            border: 1px solid #9ca3af; /* gray-400 */
            color: #111827;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6; /* gray-100 */
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 18px;
            color: #ffffff;
        }

        .badge-on-time {
            background-color: #15803d; /* green-700 */
        }

        .badge-overdue {
            background-color: #b91c1c; /* red-700 */
        }
    </style>


    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- ================================================== --}}
                {{-- TÍTULO --}}
                {{-- ================================================== --}}

                <h3 class="text-[24px] font-semibold text-gray-900">
                    Historial de facturas
                </h3>

                <p class="mt-1 text-[21px] text-gray-600">
                    Consulte todas las facturas pagadas para los documentos
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
                                        class="px-4 py-3 text-left text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Documento
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Cliente
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Servicio
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Emisión
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Vencimiento
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Importe pagado
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Estado
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right text-[21px]
                                               font-medium text-gray-500
                                               uppercase"
                                    >
                                        Acciones
                                    </th>

                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">

                                @foreach($invoices as $invoice)

                                    @php
                                        /*
                                         * Pagada después del vencimiento =
                                         * se cobró el precio vencido.
                                         */
                                        $pagadaVencida =
                                            $invoice->paid_at
                                            && $invoice->due_date
                                            && $invoice->paid_at->gt($invoice->due_date);
                                    @endphp

                                    <tr>

                                        <td
                                            class="px-4 py-4 text-[21px]
                                                   text-gray-900"
                                        >
                                            {{ $invoice->client_document }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-[21px]
                                                   text-gray-900"
                                        >
                                            {{ $invoice->client_name }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-[21px]
                                                   text-gray-900"
                                        >
                                            {{ $invoice->service_name }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-[21px]
                                                   text-gray-600"
                                        >
                                            {{ $invoice->issued_at }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-[21px]
                                                   text-gray-600"
                                        >
                                            {{ $invoice->due_date }}
                                        </td>

                                        <td
                                            class="px-4 py-4 text-[21px]
                                                   font-medium text-gray-900"
                                        >
                                            ${{ number_format(
                                                $invoice->amount_paid,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                            <div class="text-[21px] font-normal text-gray-600">
                                                {{ $pagadaVencida ? '(precio vencido)' : '(precio normal)' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-4 text-[21px]">

                                            @if($pagadaVencida)

                                                <span class="badge badge-overdue">
                                                    Vencida
                                                </span>

                                            @else

                                                <span class="badge badge-on-time">
                                                    A tiempo
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

                                                <a
                                                    href="{{ route(
                                                        'dashboard.invoices.pdf',
                                                        $invoice
                                                    ) }}"
                                                    class="btn btn-secondary"
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

                    <p class="mt-6 text-[21px] text-gray-600">
                        No hay facturas pagadas para los documentos agregados.
                    </p>

                @endif


                {{-- ================================================== --}}
                {{-- VOLVER --}}
                {{-- ================================================== --}}

                <div class="mt-6">

                    <a
                        href="{{ route('dashboard') }}"
                        class="btn btn-primary"
                    >
                        Volver al dashboard
                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>