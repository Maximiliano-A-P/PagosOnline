<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[4vh]">
            Editar servicio
        </h2>
    </x-slot>

    <style>
        .card-header {
            background-color: #111827; /* gray-900 */
            padding: 20px 24px;
        }

        .card-header h3 {
            color: #ffffff;
            font-weight: 600;
            font-size: 20px;
            margin: 0;
        }

        .card-header p {
            color: #d1d5db; /* gray-300 */
            font-size: 14px;
            margin: 8px 0 0;
        }

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
    </style>

    <div class="py-12">

        <div class="max-w-3xl mx-auto px-6 lg:px-8">

            {{-- Errores --}}
            @if($errors->any())

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm"
                >
                    <ul class="list-disc list-inside space-y-1 text-[3vh]">

                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>
                </div>

            @endif


            {{-- Tarjeta principal --}}
            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                {{-- Encabezado --}}
                <div class="card-header">

                    <h3>
                        Editar servicio
                    </h3>

                    <p>
                        Modificá los datos del servicio.
                    </p>

                </div>


                {{-- Formulario --}}
                <div class="p-6">

                    <form
                        action="{{ route('admin.services.update', $service) }}"
                        method="POST"
                        class="space-y-7"
                    >

                        @csrf
                        @method('PUT')


                        {{-- Nombre --}}
                        <div>

                            <label
                                for="service"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Nombre del servicio
                            </label>

                            <input
                                type="text"
                                id="service"
                                name="service"
                                value="{{ old('service', $service->service) }}"
                                required
                                maxlength="255"
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


                        {{-- Precio --}}
                        <div>

                            <label
                                for="price"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Precio
                            </label>

                            <input
                                type="number"
                                id="price"
                                name="price"
                                value="{{ old('price', $service->price) }}"
                                step="0.01"
                                min="0"
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

                        </div>


                        {{-- Día de vencimiento --}}
                        <div>

                            <label
                                for="due_day"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Día de vencimiento
                            </label>

                            <input
                                type="number"
                                id="due_day"
                                name="due_day"
                                value="{{ old('due_day', $service->due_day) }}"
                                min="1"
                                max="31"
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

                            <p class="mt-2 text-gray-700 text-[3vh]">
                                Día del mes en que vence el servicio.
                            </p>

                        </div>


                        {{-- Precio vencido --}}
                        <div>

                            <label
                                for="overdue_price"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Precio vencido
                            </label>

                            <input
                                type="number"
                                id="overdue_price"
                                name="overdue_price"
                                value="{{ old('overdue_price', $service->overdue_price) }}"
                                step="0.01"
                                min="0"
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

                        </div>


                        {{-- Período --}}
                        <div>

                            <label
                                for="period"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Período de facturación
                            </label>

                            <input
                                type="number"
                                id="period"
                                name="period"
                                value="{{ old('period', $service->period) }}"
                                min="1"
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

                            <p class="mt-2 text-gray-700 text-[3vh]">
                                Cantidad de meses entre cada factura.
                                Por ejemplo, 1 = mensual y 3 = trimestral.
                            </p>

                        </div>


                        {{-- Botones --}}
                        <div class="pt-4 flex items-center justify-end gap-4">

                            <a
                                href="{{ route('admin.services.index') }}"
                                class="btn btn-secondary"
                            >
                                Cancelar
                            </a>

                            <button
                                type="submit"
                                class="btn"
                            >
                                Guardar cambios
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>