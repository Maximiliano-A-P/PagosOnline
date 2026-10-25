<x-app-layout>

    <x-slot name="header">

        <div>
            <h2 class="font-semibold text-white leading-tight text-[32px]">
                Agregar servicio
            </h2>

            <p class="mt-2 text-gray-200 text-[21px]">
                Administrá los servicios del cliente seleccionado.
            </p>
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

        /* ---- Desplegable de sugerencias del buscador ---- */
        .ac-wrap {
            position: relative;
        }

        .ac-list {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            z-index: 30;
            margin: 4px 0 0;
            padding: 0;
            list-style: none;
            background-color: #ffffff;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            max-height: 350px;
            overflow-y: auto;
        }

        .ac-list[hidden] {
            display: none;
        }

        /*
         * Espacio de fondo (fuera de la tarjeta blanca) para que el
         * desplegable no estire la página al abrirse. Siempre está,
         * haya o no búsqueda activa.
         *
         * Cuenta desde el borde de abajo del input:
         *   350px + 4px  = lista (.ac-list) + su margen superior
         *   15px         = espacio de sobra
         *   -150px       = lo que ya hay debajo del input dentro de la
         *                  tarjeta (texto de ayuda + botones + márgenes)
         *   -48px        = el pb-12 que ya tiene el contenedor de la vista
         * Total = 171px. Si el espacio no coincide, tocar solo este valor.
         */
        .ac-space {
            height: 171px;
        }

        .ac-item {
            box-sizing: border-box;
            padding: 10px 14px;
            font-size: 21px;
            color: #111827;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
            line-height: 30px;
        }

        .ac-item:last-child {
            border-bottom: 0;
        }

        .ac-item small {
                display: block;
                font-size: 18px;
                line-height: 26px;
                color: #4b5563;
        }

        .ac-item.is-active,
        .ac-item:hover {
            background-color: #f3f4f6;
        }
    </style>


    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            {{-- Errores --}}
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


            <div class="bg-white border border-gray-300 rounded-lg shadow-sm">

                {{-- ================================================== --}}
                {{-- Datos del cliente --}}
                {{-- ================================================== --}}

                <div class="bg-white border-b border-gray-300 px-6 py-6">

                    <h3 class="font-semibold text-gray-900 text-[24px]">
                        Cliente seleccionado
                    </h3>

                    <div class="mt-5 space-y-2">

                        <p class="text-gray-900 text-[21px]">
                            <span class="font-semibold">
                                Nombre:
                            </span>

                            {{ $client->name }}
                        </p>

                        <p class="text-gray-900 text-[21px]">
                            <span class="font-semibold">
                                Documento:
                            </span>

                            {{ $client->document }}
                        </p>

                    </div>

                </div>


                {{-- ================================================== --}}
                {{-- Servicios actuales --}}
                {{-- ================================================== --}}

                <div class="px-6 py-6 border-b border-gray-300">

                    <h3 class="font-semibold text-gray-900 text-[24px]">
                        Servicios actuales
                    </h3>


                    @if ($client->services->count())

                        <div class="mt-5 space-y-4">

                            @foreach ($client->services as $service)

                                <div
                                    class="flex flex-col sm:flex-row
                                           sm:items-center sm:justify-between
                                           gap-4
                                           bg-gray-100
                                           border border-gray-300
                                           rounded-lg
                                           p-4"
                                >

                                    <div>

                                        <p class="font-semibold text-gray-900 text-[21px]">
                                            {{ $service->service }}
                                        </p>

                                        <p class="mt-1 text-gray-700 text-[21px]">
                                            ${{ number_format(
                                                $service->price,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                        </p>

                                    </div>


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
                            class="mt-5 bg-gray-100 border border-gray-300
                                   rounded-lg px-5 py-4"
                        >
                            <p class="text-gray-900 text-[21px]">
                                Este cliente no tiene servicios asignados.
                            </p>
                        </div>

                    @endif

                </div>


                {{-- ================================================== --}}
                {{-- Agregar servicio --}}
                {{-- ================================================== --}}

                <div class="px-6 py-6">

                    <h3 class="font-semibold text-gray-900 text-[24px]">
                        Agregar servicio
                    </h3>


                    <form
                        method="POST"
                        action="{{ route(
                            'admin.client-services.store',
                            $client
                        ) }}"
                        class="mt-6 space-y-7"
                    >

                        @csrf


                        <div>

                            <label
                                for="service_id"
                                class="block font-semibold
                                       text-gray-900 text-[21px]"
                            >
                                Servicio
                            </label>

                            <input
                                type="hidden"
                                id="service_id"
                                name="service_id"
                                value="{{ old('service_id') }}"
                            >

                            <div class="ac-wrap">

                                <input
                                    type="text"
                                    id="service_name"
                                    name="service_name"
                                    value="{{ old('service_name') }}"
                                    placeholder="Nombre del servicio"
                                    autocomplete="off"
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

                                <ul
                                    id="serviceList"
                                    class="ac-list"
                                    hidden
                                ></ul>

                            </div>

                            <p class="mt-2 text-gray-700 text-[21px]">
                                Escribí el nombre y elegí el servicio de la lista.
                            </p>

                            @error('service_id')

                                <p class="mt-2 text-red-700 text-[21px]">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Botones --}}
                        <div class="pt-4 flex items-center justify-end gap-4">

                            <a
                                href="{{ route('admin.client-services.index') }}"
                                class="btn"
                            >
                                Volver
                            </a>


                            <button
                                type="submit"
                                class="btn"
                            >
                                Agregar servicio
                            </button>

                        </div>

                    </form>

                </div>

            </div>

            {{-- Espacio de fondo para el desplegable del buscador --}}
            <div class="ac-space" aria-hidden="true"></div>

        </div>

    </div>

    <script src="{{ asset('js/search-dropdown.js') }}"></script>
    <script>
        const money = new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
        });

        searchDropdown({
            inputId: 'service_name',
            listId: 'serviceList',
            sources: [{
                url: @json(route('admin.client-services.search-services', $client)),
                value: function (s) {
                    return s.name;
                },
                render: function (s, li) {
                    li.textContent = s.name;

                    const small = document.createElement('small');
                    small.textContent = money.format(s.price) + ' · cada ' + s.period + ' mes(es)';
                    li.appendChild(small);
                },
            }],

            // Al elegir: se guarda el id y NO se envía el formulario.
            onPick: function (s) {
                document.getElementById('service_id').value = s.id;
            },

            // Si se vuelve a escribir, se descarta la selección anterior.
            onType: function () {
                document.getElementById('service_id').value = '';
            },
        });
    </script>

</x-app-layout>