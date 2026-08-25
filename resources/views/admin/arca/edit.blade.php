<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-white leading-tight text-[3vh]">
                Configuración ARCA
            </h2>

            <a
                href="{{ route('admin.dashboard') }}"
                class="btn"
            >
                Volver
            </a>
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

        .info-box {
            background-color: #111827; /* gray-900 */
            border-radius: 8px;
            padding: 20px;
        }

        .info-box h4 {
            color: #ffffff;
            font-weight: 600;
            font-size: 16px;
            margin: 0;
        }

        .info-box p {
            color: #d1d5db; /* gray-300 */
            font-size: 14px;
            margin: 8px 0 0;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Mensaje de éxito --}}
            @if (session('success'))
                <div
                    class="mb-6 p-4 bg-green-700 text-white rounded-lg shadow-sm text-[3vh]"
                >
                    {{ session('success') }}
                </div>
            @endif

            {{-- Errores de validación --}}
            @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-700 text-white rounded-lg shadow-sm"
                >
                    <ul class="list-disc list-inside text-[3vh]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900">

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 text-[4vh]">
                            Datos de ARCA
                        </h3>

                        <p class="mt-2 text-gray-700 text-[3vh]">
                            Configuración necesaria para la comunicación
                            del sistema con ARCA.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.arca.update') }}"
                        class="space-y-8"
                    >
                        @csrf
                        @method('PUT')

                        {{-- CUIT --}}
                        <div>
                            <label
                                for="cuit"
                                class="block font-medium text-gray-900 text-[3vh]"
                            >
                                CUIT
                            </label>

                            <input
                                id="cuit"
                                name="cuit"
                                type="text"
                                value="{{ old('cuit', $config?->cuit) }}"
                                required
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[3vh] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-gray-700 text-[3vh]">
                                CUIT de la empresa utilizada para operar
                                con ARCA.
                            </p>
                        </div>

                        {{-- Certificado --}}
                        <div>
                            <label
                                for="certificate_path"
                                class="block font-medium text-gray-900 text-[3vh]"
                            >
                                Ruta del certificado
                            </label>

                            <input
                                id="certificate_path"
                                name="certificate_path"
                                type="text"
                                value="{{ old('certificate_path', $config?->certificate_path) }}"
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[3vh] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-gray-700 text-[3vh]">
                                Ubicación del certificado digital utilizado
                                para autenticarse ante ARCA.
                            </p>
                        </div>

                        {{-- Clave privada --}}
                        <div>
                            <label
                                for="private_key_path"
                                class="block font-medium text-gray-900 text-[3vh]"
                            >
                                Ruta de la clave privada
                            </label>

                            <input
                                id="private_key_path"
                                name="private_key_path"
                                type="text"
                                value="{{ old('private_key_path', $config?->private_key_path) }}"
                                class="mt-2 block w-full rounded-md border-gray-400 shadow-sm text-[3vh] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-gray-700 text-[3vh]">
                                Ubicación de la clave privada utilizada
                                para la autenticación.
                            </p>
                        </div>

                        {{-- Información del token --}}
                        <div class="info-box">

                            <h4>
                                Estado de autenticación
                            </h4>

                            @if ($config?->token)
                                <p>
                                    Token configurado.
                                </p>

                                @if ($config->token_expires_at)
                                    <p>
                                        Vencimiento:
                                        {{ $config->token_expires_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            @else
                                <p>
                                    No existe un token de autenticación
                                    configurado actualmente.
                                </p>
                            @endif

                            <p>
                                El token será gestionado automáticamente
                                por la integración con ARCA.
                            </p>

                        </div>

                        {{-- Botón --}}
                        <div class="flex items-center justify-end" style="margin-top: 8px;">

                            <button
                                type="submit"
                                class="btn"
                            >
                                Guardar configuración
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>
    </div>

</x-app-layout>