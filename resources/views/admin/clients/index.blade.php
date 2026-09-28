<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Clientes
        </h2>

    </x-slot>


    <style>
        /*
         * Botones: misma medida para <a> y <button>
         * (mismo alto, mismo tamaño de letra, misma alineación).
         * Medidas tomadas del dashboard general.
         */
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

        .table-header th {
            color: #000000;
            font-weight: 600;
            padding: 16px 24px;
            text-align: left;
            font-size: 21px;
            border-bottom: 2px solid #d1d5db;
        }

        .row-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .row-actions form {
            margin: 0;
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
            {{-- Errores --}}
            {{-- ================================================== --}}

            @if ($errors->any())

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm"
                >
                    <ul class="list-disc list-inside space-y-1 text-[21px]">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>
                </div>

            @endif


            {{-- ================================================== --}}
            {{-- Acciones --}}
            {{-- ================================================== --}}

            <div class="mb-8 flex items-center justify-between">

                <h3 class="font-semibold text-white text-[32px]">
                    Clientes registrados
                </h3>

                <div class="flex items-center gap-4">

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="btn"
                    >
                        Volver al panel
                    </a>

                    <a
                        href="{{ route('admin.clients.create') }}"
                        class="btn"
                    >
                        Crear cliente
                    </a>

                </div>

            </div>


            {{-- ================================================== --}}
            {{-- Buscador --}}
            {{-- ================================================== --}}

            <div class="mb-8 bg-white border border-gray-300 rounded-lg shadow-sm">

                <div class="p-6">

                    <form
                        method="GET"
                        action="{{ route('admin.clients.index') }}"
                    >

                        <div class="flex flex-col lg:flex-row gap-4">

                            <div class="flex-1">

                                <label
                                    for="search"
                                    class="block font-semibold text-gray-900 text-[21px]"
                                >
                                    Buscar cliente
                                </label>

                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Nombre o documento"
                                    class="mt-2 block w-full rounded-md
                                           border-gray-400
                                           bg-white
                                           text-gray-900
                                           text-[21px]
                                           shadow-sm
                                           focus:border-indigo-600
                                           focus:ring-indigo-600"
                                    style="height: 42px;"
                                >

                            </div>


                            <div class="flex items-end gap-3">

                                <button
                                    type="submit"
                                    class="btn"
                                >
                                    Buscar
                                </button>


                                @if (request('search'))

                                    <a
                                        href="{{ route('admin.clients.index') }}"
                                        class="btn"
                                    >
                                        Limpiar
                                    </a>

                                @endif

                            </div>

                        </div>

                    </form>

                </div>

            </div>


            {{-- ================================================== --}}
            {{-- Tabla --}}
            {{-- ================================================== --}}

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="p-6">

                    @php
                        $condicionesIva = [
                            1  => 'IVA Responsable Inscripto',
                            4  => 'IVA Sujeto Exento',
                            5  => 'Consumidor Final',
                            6  => 'Responsable Monotributo',
                            7  => 'Sujeto No Categorizado',
                            8  => 'Proveedor del Exterior',
                            9  => 'Cliente del Exterior',
                            10 => 'IVA Liberado – Ley N° 19.640',
                            13 => 'Monotributista Social',
                            15 => 'IVA No Alcanzado',
                            16 => 'Monotributo Trabajador Independiente Promovido',
                        ];
                    @endphp

                    @if ($clients->count())

                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-gray-300">

                                <thead class="table-header">

                                    <tr>

                                        <th>
                                            ID
                                        </th>

                                        <th>
                                            Nombre
                                        </th>

                                        <th>
                                            Documento
                                        </th>

                                        <th>
                                            CUIT
                                        </th>

                                        <th>
                                            Condición frente al IVA
                                        </th>

                                        <th>
                                            Creado
                                        </th>

                                        <th>
                                            Acciones
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="bg-white divide-y divide-gray-200">

                                    @foreach ($clients as $client)

                                        <tr class="hover:bg-gray-50">

                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       text-[21px]"
                                            >
                                                {{ $client->id }}
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       font-medium
                                                       text-[21px]"
                                            >
                                                {{ $client->name }}
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       text-[21px]"
                                            >
                                                {{ $client->document }}
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       text-[21px]"
                                            >
                                                {{ $client->cuit ?: 'N/A' }}
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       text-[21px]"
                                            >
                                                @php
                                                    $codigoIva = $client->arca_iva_condition ?: 5;
                                                @endphp

                                                {{ $codigoIva }}
                                                @if (isset($condicionesIva[$codigoIva]))
                                                    — {{ $condicionesIva[$codigoIva] }}
                                                @endif
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       text-[21px]"
                                            >
                                                {{ $client->created_at->format('d/m/Y H:i') }}
                                            </td>


                                            <td class="px-6 py-5 text-[21px]">

                                                <div class="row-actions">

                                                    <a
                                                        href="{{ route(
                                                            'admin.clients.edit',
                                                            $client
                                                        ) }}"
                                                        class="btn"
                                                    >
                                                        Editar
                                                    </a>


                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'admin.clients.destroy',
                                                            $client
                                                        ) }}"
                                                        onsubmit="return confirm(
                                                            '¿Eliminar este cliente?'
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

                                    @endforeach

                                </tbody>

                            </table>

                        </div>


                        {{-- Paginación --}}
                        <div class="mt-8">

                            {{ $clients->links() }}

                        </div>

                    @else

                        <div class="py-12 text-center">

                            <p class="text-gray-900 font-semibold text-[21px]">
                                No hay clientes registrados.
                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-app-layout>