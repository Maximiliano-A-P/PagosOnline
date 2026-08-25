<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <h2 class="font-semibold text-white leading-tight text-[4vh]">
                Facturas
            </h2>

            <div class="flex items-center gap-4">

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
            background-color: #111827; /* gray-900 */
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

        .btn-success {
            background-color: #15803d; /* green-700 */
            border-color: #15803d;
        }

        .btn-success:hover {
            background-color: #166534; /* green-800 */
        }

        .btn-danger {
            background-color: #b91c1c; /* red-700 */
            border-color: #b91c1c;
        }

        .btn-danger:hover {
            background-color: #991b1b; /* red-800 */
        }

        .table-header th {
            color: #000000;
            font-weight: 600;
            padding: 16px 24px;
            text-align: left;
            font-size: 14px;
            border-bottom: 2px solid #d1d5db;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 13px;
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

        <div class="max-w-7xl mx-auto px-6 lg:px-8">

            {{-- Mensaje de éxito --}}
            @if (session('success'))

                <div
                    class="mb-8 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[3vh]"
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- Mensaje de error --}}
            @if (session('error'))

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm text-[3vh]"
                >
                    {{ session('error') }}
                </div>

            @endif


            {{-- Encabezado --}}
            <div class="mb-8">

                <h3 class="font-semibold text-white text-[4vh]">
                    Facturas registradas
                </h3>

            </div>


            {{-- Búsqueda y filtros --}}
            <div class="bg-white border border-gray-300 rounded-lg shadow-sm mb-8">

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


            {{-- Tabla --}}
            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="p-6">

                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-gray-300">

                            <thead class="table-header">

                                <tr>

                                    <th>
                                        Emisión
                                    </th>

                                    <th>
                                        Cliente
                                    </th>

                                    <th>
                                        Servicio
                                    </th>

                                    <th>
                                        Precio
                                    </th>

                                    <th>
                                        Vencimiento
                                    </th>

                                    <th>
                                        Estado
                                    </th>

                                    <th>
                                        Acciones
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="bg-white divide-y divide-gray-200">

                                @forelse ($invoices as $invoice)

                                    <tr class="hover:bg-gray-50">

                                        {{-- Emisión --}}
                                        <td class="px-6 py-5 text-gray-900 text-[3vh] whitespace-nowrap">
                                            {{ $invoice->issued_at->format('d/m/Y') }}
                                        </td>


                                        {{-- Cliente --}}
                                        <td class="px-6 py-5 whitespace-nowrap">

                                            <div class="text-gray-900 font-medium text-[3vh]">
                                                {{ $invoice->client_name }}
                                            </div>

                                            <div class="text-gray-700 text-[3vh]">
                                                {{ $invoice->client_document }}
                                            </div>

                                        </td>


                                        {{-- Servicio --}}
                                        <td class="px-6 py-5 text-gray-900 text-[3vh] whitespace-nowrap">
                                            {{ $invoice->service_name }}
                                        </td>


                                        {{-- Precio --}}
                                        <td class="px-6 py-5 text-gray-900 text-[3vh] whitespace-nowrap">
                                            ${{ number_format($invoice->price, 2, ',', '.') }}
                                        </td>


                                        {{-- Vencimiento --}}
                                        <td class="px-6 py-5 text-gray-900 text-[3vh] whitespace-nowrap">
                                            {{ $invoice->due_date->format('d/m/Y') }}
                                        </td>


                                        {{-- Estado --}}
                                        <td class="px-6 py-5 whitespace-nowrap">

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


                                        {{-- Acciones --}}
                                        <td class="px-6 py-5 text-[3vh]">

                                            <div class="flex items-center gap-4">

                                                <a
                                                    href="{{ route('admin.invoices.show', $invoice) }}"
                                                    class="btn"
                                                >
                                                    Ver
                                                </a>


                                                @if ($invoice->payment_status !== 'paid')

                                                    <a
                                                        href="{{ route('admin.invoices.payment', $invoice) }}"
                                                        class="btn"
                                                    >
                                                        Registrar pago
                                                    </a>

                                                @endif


                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.invoices.destroy', $invoice) }}"
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

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="7"
                                            class="px-6 py-10 text-center
                                                   text-gray-900 text-[3vh]"
                                        >
                                            No hay facturas registradas.
                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- Paginación --}}
                    <div class="mt-8">
                        {{ $invoices->links() }}
                    </div>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>