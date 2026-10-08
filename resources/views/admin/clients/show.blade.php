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
        .ledger th.num,
        .ledger td.num {
            text-align: right;
            white-space: nowrap;
        }

        .ledger-row-strong td {
            background-color: #f3f4f6; /* gray-100 */
            font-weight: 600;
        }

        .ledger-input {
            box-sizing: border-box;
            height: 42px;
            padding: 6px 12px;
            border: 1px solid #9ca3af; /* gray-400 */
            border-radius: 6px;
            font-size: 21px;
            font-family: inherit;
            background-color: #ffffff;
            color: #111827;
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
                                Teléfono
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->phone ?: '—' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Email
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->email ?: '—' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Dirección
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $client->address ?: '—' }}
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
            {{-- Tarjeta: saldos a fecha --}}
            {{-- ================================================== --}}

            @php
                $money = function (float $amount): string {
                    return ($amount < 0 ? '-' : '')
                        . '$'
                        . number_format(abs($amount), 2, ',', '.');
                };
            @endphp

            <div id="saldos" class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden mb-10">

                <div class="card-header">

                    <h3>
                        Saldos a fecha
                    </h3>

                    <p>
                        Movimientos de cuenta: las facturas suman al debe y los pagos al haber.
                    </p>

                </div>


                <div class="p-6">

                    {{-- Filtro de fechas --}}
                    <form
                        method="GET"
                        action="{{ route('admin.clients.show', $client) }}#saldos"
                        class="flex flex-wrap items-end gap-4 mb-8"
                    >

                        <div>
                            <label for="desde" class="block font-semibold text-gray-700 text-[21px] mb-2">
                                Desde
                            </label>

                            <input
                                id="desde"
                                name="desde"
                                type="{{ $desde ? 'date' : 'text' }}"
                                value="{{ $desde?->format('Y-m-d') }}"
                                placeholder="Desde el inicio"
                                class="ledger-input"
                                onfocus="this.type='date'; try { this.showPicker(); } catch (e) {}"
                                onblur="if (!this.value) this.type='text';"
                            >
                        </div>

                        <div>
                            <label for="hasta" class="block font-semibold text-gray-700 text-[21px] mb-2">
                                Hasta
                            </label>

                            <input
                                id="hasta"
                                name="hasta"
                                type="{{ $hasta ? 'date' : 'text' }}"
                                value="{{ $hasta?->format('Y-m-d') }}"
                                placeholder="Hasta hoy"
                                class="ledger-input"
                                onfocus="this.type='date'; try { this.showPicker(); } catch (e) {}"
                                onblur="if (!this.value) this.type='text';"
                            >
                        </div>

                        <button type="submit" class="btn">
                            Filtrar
                        </button>

                        @if ($desde || $hasta)
                            <a
                                href="{{ route('admin.clients.show', $client) }}#saldos"
                                class="btn"
                            >
                                Limpiar
                            </a>
                        @endif

                    </form>

                    @if ($errors->has('desde') || $errors->has('hasta'))
                        <div class="mb-6 rounded-lg bg-red-700 text-white px-6 py-4 text-[21px]">
                            {{ $errors->first('desde') ?: $errors->first('hasta') }}
                        </div>
                    @endif


                    <div class="overflow-x-auto">

                        <table class="ledger min-w-full divide-y divide-gray-300">

                            <thead class="table-header">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Detalle</th>
                                    <th class="num">Debe</th>
                                    <th class="num">Haber</th>
                                    <th class="num">Saldo</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">

                                {{-- Saldo anterior al "desde" --}}
                                @if ($desde)
                                    <tr class="ledger-row-strong">
                                        <td class="px-6 py-4 text-gray-900 text-[21px]" colspan="4">
                                            Saldo al {{ $desde->copy()->subDay()->format('d/m/Y') }}
                                        </td>
                                        <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $money($saldoAnterior) }}
                                        </td>
                                    </tr>
                                @endif


                                @forelse ($asientos as $asiento)

                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $asiento['fecha']->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $asiento['detalle'] }}
                                        </td>
                                        <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $asiento['debe'] > 0 ? $money($asiento['debe']) : '' }}
                                        </td>
                                        <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $asiento['haber'] > 0 ? $money($asiento['haber']) : '' }}
                                        </td>
                                        <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $money($asiento['saldo']) }}
                                        </td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td class="px-6 py-8 text-center text-gray-800 text-[21px]" colspan="5">
                                            No hay movimientos en el período seleccionado.
                                        </td>
                                    </tr>

                                @endforelse


                                {{-- Totales del período --}}
                                @if ($asientos->isNotEmpty())
                                    <tr class="ledger-row-strong">
                                        <td class="px-6 py-4 text-gray-900 text-[21px]" colspan="2">
                                            Totales
                                        </td>
                                        <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $money($totalDebe) }}
                                        </td>
                                        <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $money($totalHaber) }}
                                        </td>
                                        <td class="px-6 py-4"></td>
                                    </tr>
                                @endif


                                {{-- Saldo al final del rango --}}
                                <tr class="ledger-row-strong">
                                    <td class="px-6 py-4 text-gray-900 text-[21px]" colspan="4">
                                        Saldo al {{ $hasta ? $hasta->format('d/m/Y') : 'día de hoy' }}
                                    </td>
                                    <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                        {{ $money($saldoFinal) }}
                                    </td>
                                </tr>


                                {{-- Saldo al día de hoy (si el rango termina antes) --}}
                                @if ($hasta && $hasta->lt($hoy))
                                    <tr class="ledger-row-strong">
                                        <td class="px-6 py-4 text-gray-900 text-[21px]" colspan="4">
                                            Saldo al día de hoy
                                        </td>
                                        <td class="num px-6 py-4 text-gray-900 text-[21px]">
                                            {{ $money($saldoHoy) }}
                                        </td>
                                    </tr>
                                @endif

                            </tbody>

                        </table>

                    </div>

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