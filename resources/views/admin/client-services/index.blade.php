<x-app-layout>

    <x-slot name="header">

        <div>
            <h2 class="font-semibold text-white leading-tight text-[4vh]">
                Servicios de clientes
            </h2>

            <p class="mt-2 text-gray-200 text-[3vh]">
                Buscá un cliente para consultar y modificar sus servicios.
            </p>
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

        .card-header {
            padding: 20px 24px;
        }

        .card-header h3 {
            color: #111827;
            font-weight: 600;
            font-size: 20px;
            margin: 0;
        }

        .card-header p {
            color: #374151; /* gray-700 */
            font-size: 14px;
            margin: 8px 0 0;
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
            {{-- Buscador --}}
            {{-- ================================================== --}}

            <div class="mb-8 bg-white border border-gray-300 rounded-lg shadow-sm">

                <div class="p-6">

                    <form
                        method="GET"
                        action="{{ route('admin.client-services.index') }}"
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
                                    id="search"
                                    name="search"
                                    type="text"
                                    value="{{ request('search') }}"
                                    placeholder="Nombre o documento..."
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
                                        href="{{ route('admin.client-services.index') }}"
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
            {{-- Resultados --}}
            {{-- ================================================== --}}

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="p-6">

                    @forelse ($clients as $client)

                        {{-- Cliente --}}
                        <div
                            class="border border-gray-300 rounded-lg
                                   overflow-hidden mb-6 last:mb-0"
                        >

                            {{-- Encabezado del cliente --}}
                            <div class="card-header">

                                <div class="flex flex-col lg:flex-row
                                            lg:items-center lg:justify-between
                                            gap-4">

                                    <div>

                                        <h3>
                                            {{ $client->name }}
                                        </h3>

                                        <p>
                                            Documento: {{ $client->document }}
                                        </p>

                                    </div>


                                    {{-- Agregar servicio --}}
                                    <a
                                        href="{{ route(
                                            'admin.client-services.create',
                                            $client
                                        ) }}"
                                        class="btn"
                                    >
                                        Agregar servicio
                                    </a>

                                </div>

                            </div>


                            {{-- Servicios asignados --}}
                            <div class="p-6">

                                <h4 class="font-semibold text-gray-900 text-[3vh] mb-5">
                                    Servicios asignados
                                </h4>


                                @if ($client->services->count())

                                    <div class="space-y-4">

                                        @foreach ($client->services as $service)

                                            <div
                                                class="flex flex-col lg:flex-row
                                                       lg:items-center lg:justify-between
                                                       gap-4
                                                       p-4
                                                       border border-gray-300
                                                       rounded-lg
                                                       bg-white"
                                            >

                                                <div>

                                                    <p class="font-semibold text-gray-900 text-[3vh]">
                                                        {{ $service->service }}
                                                    </p>

                                                    <p class="mt-1 text-gray-700 text-[3vh]">
                                                        ${{ number_format(
                                                            $service->price,
                                                            2,
                                                            ',',
                                                            '.'
                                                        ) }}
                                                    </p>

                                                </div>


                                                {{-- Quitar servicio --}}
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'admin.client-services.destroy',
                                                        $client
                                                    ) }}"
                                                    onsubmit="return confirm(
                                                        '¿Querés quitar este servicio del cliente?'
                                                    );"
                                                >

                                                    @csrf
                                                    @method('DELETE')

                                                    <input
                                                        type="hidden"
                                                        name="service_id"
                                                        value="{{ $service->id }}"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn"
                                                    >
                                                        Quitar
                                                    </button>

                                                </form>

                                            </div>

                                        @endforeach

                                    </div>

                                @else

                                    <div
                                        class="border border-gray-300 rounded-lg
                                               bg-gray-100 px-5 py-5"
                                    >
                                        <p class="text-gray-900 text-[3vh]">
                                            Este cliente no tiene servicios asignados.
                                        </p>
                                    </div>

                                @endif

                            </div>

                        </div>

                    @empty

                        <div class="py-12 text-center">

                            @if (request('search'))

                                <p class="text-gray-900 font-semibold text-[3vh]">
                                    No se encontró ningún cliente con esa búsqueda.
                                </p>

                            @else

                                <p class="text-gray-900 font-semibold text-[3vh]">
                                    Buscá un cliente por nombre o documento.
                                </p>

                            @endif

                        </div>

                    @endforelse


                    {{-- ================================================== --}}
                    {{-- Paginación --}}
                    {{-- ================================================== --}}

                    @if ($clients->hasPages())

                        <div class="mt-8">

                            {{ $clients->links() }}

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-app-layout>