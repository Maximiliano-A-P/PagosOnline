<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[4vh]">
            Panel de administración
        </h2>
    </x-slot>

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
            {{-- Gestión --}}
            {{-- ================================================== --}}

            <div class="mb-16">

                <h3 class="font-semibold text-white mb-6 text-[4vh]">
                    Gestión
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    {{-- Clientes --}}
                    <a
                        href="{{ route('admin.clients.index') }}"
                        class="block bg-white border border-gray-300 rounded-lg
                               shadow-sm hover:bg-gray-50 transition"
                    >
                        <div class="p-6">

                            <h4 class="font-semibold text-gray-900 text-[3.5vh]">
                                Clientes
                            </h4>

                            <p class="mt-3 text-gray-700 text-[3vh]">
                                Crear, consultar, editar y eliminar clientes.
                            </p>

                        </div>
                    </a>


                    {{-- Servicios --}}
                    <a
                        href="{{ route('admin.services.index') }}"
                        class="block bg-white border border-gray-300 rounded-lg
                               shadow-sm hover:bg-gray-50 transition"
                    >
                        <div class="p-6">

                            <h4 class="font-semibold text-gray-900 text-[3.5vh]">
                                Servicios
                            </h4>

                            <p class="mt-3 text-gray-700 text-[3vh]">
                                Administrar servicios, precios y períodos de facturación.
                            </p>

                        </div>
                    </a>


                    {{-- Clientes x servicios --}}
                    <a
                        href="{{ route('admin.client-services.index') }}"
                        class="block bg-white border border-gray-300 rounded-lg
                               shadow-sm hover:bg-gray-50 transition"
                    >
                        <div class="p-6">

                            <h4 class="font-semibold text-gray-900 text-[3.5vh]">
                                Servicios de los clientes
                            </h4>

                            <p class="mt-3 text-gray-700 text-[3vh]">
                                Asignar y quitar servicios a los clientes.
                            </p>

                        </div>
                    </a>

                </div>

            </div>


            {{-- ================================================== --}}
            {{-- Facturación --}}
            {{-- ================================================== --}}

            <div class="mb-16" style="margin-top: 4vh;">

                <h3 class="font-semibold text-white mb-6 text-[4vh]">
                    Facturación
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Facturas --}}
                    <a
                        href="{{ route('admin.invoices.index') }}"
                        class="block bg-white border border-gray-300 rounded-lg
                               shadow-sm hover:bg-gray-50 transition"
                    >
                        <div class="p-6">

                            <h4 class="font-semibold text-gray-900 text-[3.5vh]">
                                Facturas
                            </h4>

                            <p class="mt-3 text-gray-700 text-[3vh]">
                                Consultar, crear y gestionar las facturas.
                            </p>

                        </div>
                    </a>


                    {{-- Nueva factura --}}
                    <a
                        href="{{ route('admin.invoices.create') }}"
                        class="block bg-white border border-gray-300 rounded-lg
                               shadow-sm hover:bg-gray-50 transition"
                    >
                        <div class="p-6">

                            <h4 class="font-semibold text-gray-900 text-[3.5vh]">
                                Nueva factura
                            </h4>

                            <p class="mt-3 text-gray-700 text-[3vh]">
                                Registrar una factura manualmente.
                            </p>

                        </div>
                    </a>

                </div>

            </div>


            {{-- ================================================== --}}
            {{-- Configuración --}}
            {{-- ================================================== --}}

            <div class="mb-16" style="margin-top: 4vh;">

                <h3 class="font-semibold text-white mb-6 text-[4vh]">
                    Configuración
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- ARCA --}}
                    <a
                        href="{{ route('admin.arca.edit') }}"
                        class="block bg-white border border-gray-300 rounded-lg
                               shadow-sm hover:bg-gray-50 transition"
                    >
                        <div class="p-6">

                            <h4 class="font-semibold text-gray-900 text-[3.5vh]">
                                Configuración ARCA
                            </h4>

                            <p class="mt-3 text-gray-700 text-[3vh]">
                                Configurar los datos necesarios para la comunicación con ARCA.
                            </p>

                        </div>
                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>