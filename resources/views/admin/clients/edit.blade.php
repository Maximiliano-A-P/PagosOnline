<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Editar cliente
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
            font-size: 24px;
            margin: 0;
        }

        .card-header p {
            color: #d1d5db; /* gray-300 */
            font-size: 21px;
            margin: 8px 0 0;
        }

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

        <div class="mx-auto" style="width: 90vw;">

            {{-- Errores --}}
            @if($errors->any())

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm"
                >
                    <ul class="list-disc list-inside space-y-1 text-[21px]">

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
                        Editar datos del cliente
                    </h3>

                    <p>
                        Modificá los datos del cliente.
                    </p>

                </div>


                {{-- Formulario --}}
                <div class="p-6">

                    <form
                        action="{{ route('admin.clients.update', $client) }}"
                        method="POST"
                        class="space-y-7"
                    >

                        @csrf
                        @method('PUT')


                        {{-- Nombre --}}
                        <div>

                            <label
                                for="name"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                Nombre
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $client->name) }}"
                                required
                                maxlength="255"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[21px]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                        </div>


                        {{-- DNI --}}
                        <div>

                            <label
                                for="document"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                DNI
                            </label>

                            <input
                                type="number"
                                id="document"
                                name="document"
                                value="{{ old('document', $client->document) }}"
                                min="1"
                                required
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[21px]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                        </div>


                        {{-- CUIT --}}
                        <div>

                            <label
                                for="cuit"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                CUIT (opcional)
                            </label>

                            <input
                                type="text"
                                id="cuit"
                                name="cuit"
                                value="{{ old('cuit', $client->cuit) }}"
                                inputmode="numeric"
                                maxlength="11"
                                pattern="\d{11}"
                                placeholder="Ej. 20123456789"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[21px]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            @error('cuit')
                                <p class="mt-2 text-[21px]" style="color: #b91c1c;">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        {{-- Condición frente al IVA --}}
                        <div>

                            <label
                                for="arca_iva_condition"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                Condición frente al IVA (código AFIP)
                            </label>

                            <input
                                type="number"
                                id="arca_iva_condition"
                                name="arca_iva_condition"
                                list="condicionIvaReferencia"
                                value="{{ old('arca_iva_condition', $client->arca_iva_condition) }}"
                                min="1"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[21px]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            <datalist id="condicionIvaReferencia">
                                <option value="1">IVA Responsable Inscripto</option>
                                <option value="4">IVA Sujeto Exento</option>
                                <option value="5">Consumidor Final</option>
                                <option value="6">Responsable Monotributo</option>
                            </datalist>

                        </div>


                        {{-- Botones --}}
                        <div class="pt-4 flex items-center justify-end gap-4">

                            <a
                                href="{{ route('admin.clients.index') }}"
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