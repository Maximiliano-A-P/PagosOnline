<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <h2 class="font-semibold text-white leading-tight text-[32px]">
                Detalle de cliente
            </h2>

            <div class="flex items-center gap-4">

                <a
                    href="{{ route('admin.clients.index') }}"
                    class="btn"
                >
                    Volver
                </a>

                <a
                    href="{{ route('admin.clients.edit', $client) }}"
                    class="btn"
                >
                    Editar
                </a>

            </div>

        </div>

    </x-slot>


    <style>
        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 6px 14px;
            background-color: #111827; /* gray-900 */
            border: 1px solid #111827;
            border-radius: 6px;
            font-weight: 600;
            color: #ffffff;
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

        .btn:hover {
            background-color: #374151; /* gray-700 */
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563; /* ring-gray-600 + offset */
        }

        .card-header {
            background-color: #111827; /* gray-900 */
            padding: 20px 24px;
        }

        .card-header h3 {
            color: #ffffff;
            font-weight: 600;
            font-size: 24px;
            margin: 0;
        }

        .card-header p {
            color: #d1d5db; /* gray-300 */
            font-size: 21px;
            margin: 8px 0 0;
        }

        .table-header th {
            color: #000000;
            font-weight: 600;
            padding: 16px 24px;
            text-align: left;
            font-size: 21px;
            border-bottom: 2px solid #d1d5db;
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

        .badge-paid {
            background-color: #15803d; /* green-700 */
        }

        .badge-pending {
            background-color: #111827; /* gray-900 */
        }
    </style>


    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            {{-- ================================================== --}}
            {{-- Mensaje de éxito --}}
            {{-- ================================================== --}}

            @if (session('success'))

                <div
                    class="mb-8 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- ================================================== --}}
            {{-- Tarjeta: datos del cliente --}}
            {{-- ================================================== --}}

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden mb-10">

                <div class="card-header">

                    <h3>
                        Información del cliente
                    </h3>

                </div>


                <div class="p-6">

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                ID
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                #{{ $client->id }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Nombre
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->name }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Documento (DNI)
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->document }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                CUIT
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->cuit ?? '—' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Condición frente al IVA
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                @php
                                    $condicionesIva = [
                                        1 => 'IVA Responsable Inscripto',
                                        4 => 'IVA Sujeto Exento',
                                        5 => 'Consumidor Final',
                                        6 => 'Responsable Monotributo',
                                    ];
                                @endphp

                                @if ($client->arca_iva_condition !== null)
                                    {{ $client->arca_iva_condition }}
                                    @if (isset($condicionesIva[$client->arca_iva_condition]))
                                        — {{ $condicionesIva[$client->arca_iva_condition] }}
                                    @endif
                                @else
                                    No cargada (se factura como Consumidor Final)
                                @endif
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Fecha de creación
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Última edición
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->updated_at?->format('d/m/Y H:i') ?? '—' }}
                            </dd>
                        </div>

                    </dl>

                </div>

            </div>


            {{-- ================================================== --}}
            {{-- Tarjeta: facturas del cliente --}}
            {{-- ================================================== --}}

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="card-header">

                    <h3>
                        Facturas del cliente
                    </h3>

                    <p>
                        Primero las pendientes de pago y luego las pagadas.
                    </p>

                </div>


                <div class="p-6">

                    @if ($invoices->isEmpty())

                        <div class="py-10 text-center">

                            <p class="text-gray-800 text-[21px]">
                                Este cliente no tiene facturas registradas.
                            </p>

                        </div>

                    @else

                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-gray-300">

                                <thead class="table-header">

                                    <tr>
                                        <th>ID</th>
                                        <th>Servicio</th>
                                        <th>Emisión</th>
                                        <th>Vencimiento</th>
                                        <th>Estado</th>
                                        <th>Monto</th>
                                        <th>Acciones</th>
                                    </tr>

                                </thead>


                                <tbody class="bg-white divide-y divide-gray-200">

                                    @foreach ($invoices as $invoice)

                                        <tr class="hover:bg-gray-50">

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                #{{ $invoice->id }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 font-medium text-[21px]">
                                                {{ $invoice->service_name }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                {{ $invoice->issued_at?->format('d/m/Y') ?? '—' }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
                                            </td>

                                            <td class="px-6 py-5 text-[21px]">

                                                @if ($invoice->payment_status === 'paid')

                                                    <span class="badge badge-paid">
                                                        Pagada
                                                    </span>

                                                @else

                                                    <span class="badge badge-pending">
                                                        Pendiente
                                                    </span>

                                                @endif

                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                ${{ number_format($invoice->montoACobrar(), 2, ',', '.') }}
                                            </td>

                                            <td class="px-6 py-5 text-[21px]">

                                                <a
                                                    href="{{ route('admin.invoices.show', $invoice) }}"
                                                    class="btn"
                                                >
                                                    Ver
                                                </a>

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-app-layout>