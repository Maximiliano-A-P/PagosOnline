<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-white leading-tight text-[4vh]">
            Clientes
        </h2>

    </x-slot>


    <style>
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background-color: #111827; /* gray-900 */
            border: 1px solid #111827;
            border-radius: 6px;
            font-weight: 600;
            color: #ffffff;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        button.btn {
            box-sizing: border-box;
            line-height: normal;
            appearance: none;
            -webkit-appearance: none;
            font-family: inherit;
            margin: 0;
        }

        .btn:hover {
            background-color: #374151; /* gray-700 */
        }

        .table-header th {
            color: #000000;
            font-weight: 600;
            padding: 16px 24px;
            text-align: left;
            font-size: 14px;
            border-bottom: 2px solid #d1d5db;
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563; /* ring-gray-600 + offset */
        }
    </style>


    <div class="py-12">

        <div class="max-w-7xl mx-auto px-6 lg:px-8">

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
            {{-- Errores --}}
            {{-- ================================================== --}}

            @if ($errors->any())

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm"
                >
                    <ul class="list-disc list-inside space-y-1 text-[3vh]">

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

                <h3 class="font-semibold text-white text-[4vh]">
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
                                    class="block font-semibold text-gray-900 text-[3vh]"
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
                                           text-[3vh]
                                           shadow-sm
                                           focus:border-indigo-600
                                           focus:ring-indigo-600"
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
                                                       text-[3vh]"
                                            >
                                                {{ $client->id }}
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       font-medium
                                                       text-[3vh]"
                                            >
                                                {{ $client->name }}
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       text-[3vh]"
                                            >
                                                {{ $client->document }}
                                            </td>


                                            <td
                                                class="px-6 py-5
                                                       text-gray-900
                                                       text-[3vh]"
                                            >
                                                {{ $client->created_at->format('d/m/Y H:i') }}
                                            </td>


                                            <td class="px-6 py-5 text-[3vh]">

                                                <div class="flex items-center gap-12">

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

                            <p class="text-gray-900 font-semibold text-[3vh]">
                                No hay clientes registrados.
                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-app-layout>