<x-app-layout>

    <x-slot name="header">

        <div>
            <h2 class="font-semibold text-white leading-tight text-[4vh]">
                Agregar servicio
            </h2>

            <p class="mt-2 text-gray-200 text-[3vh]">
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
    </style>


    <div class="py-12">

        <div class="max-w-3xl mx-auto px-6 lg:px-8">

            {{-- Errores --}}
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


            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                {{-- ================================================== --}}
                {{-- Datos del cliente --}}
                {{-- ================================================== --}}

                <div class="bg-white border-b border-gray-300 px-6 py-6">

                    <h3 class="font-semibold text-gray-900 text-[3.5vh]">
                        Cliente seleccionado
                    </h3>

                    <div class="mt-5 space-y-2">

                        <p class="text-gray-900 text-[3vh]">
                            <span class="font-semibold">
                                Nombre:
                            </span>

                            {{ $client->name }}
                        </p>

                        <p class="text-gray-900 text-[3vh]">
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

                    <h3 class="font-semibold text-gray-900 text-[3.5vh]">
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
                            <p class="text-gray-900 text-[3vh]">
                                Este cliente no tiene servicios asignados.
                            </p>
                        </div>

                    @endif

                </div>


                {{-- ================================================== --}}
                {{-- Agregar servicio --}}
                {{-- ================================================== --}}

                <div class="px-6 py-6">

                    <h3 class="font-semibold text-gray-900 text-[3.5vh]">
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
                                       text-gray-900 text-[3vh]"
                            >
                                Servicio
                            </label>

                            <select
                                id="service_id"
                                name="service_id"
                                required
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
                                    Seleccioná un servicio
                                </option>

                                @foreach ($services as $service)

                                    <option
                                        value="{{ $service->id }}"
                                        @selected(
                                            old('service_id') == $service->id
                                        )
                                    >
                                        {{ $service->service }}
                                        — ${{ number_format(
                                            $service->price,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </option>

                                @endforeach

                            </select>

                            @error('service_id')

                                <p class="mt-2 text-red-700 text-[3vh]">
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

        </div>

    </div>

</x-app-layout>