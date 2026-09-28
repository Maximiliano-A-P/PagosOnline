<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Servicios
        </h2>
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

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563;
        }

        .btn-danger {
            background-color: #b91c1c; /* red-700 */
            border-color: #b91c1c;
        }

        .btn-danger:hover {
            background-color: #991b1b; /* red-800 */
        }

        .row-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .row-actions form {
            margin: 0;
        }

        .table-header th {
            color: #000000;
            font-weight: 600;
            padding: 16px 24px;
            text-align: left;
            font-size: 21px;
            border-bottom: 2px solid #d1d5db;
        }
    </style>

    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            {{-- Mensaje de éxito --}}
            @if(session('success'))

                <div
                    class="mb-6 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- Encabezado --}}
            <div class="mb-8 flex items-center justify-between">

                <h3 class="font-semibold text-white text-[32px]">
                    Servicios registrados
                </h3>

                <a
                    href="{{ route('admin.services.create') }}"
                    class="btn"
                >
                    Crear servicio
                </a>

            </div>


            {{-- Tabla --}}
            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="p-6">

                    @if($services->isEmpty())

                        <div class="py-10 text-center">

                            <p class="text-gray-800 text-[21px]">
                                No hay servicios registrados.
                            </p>

                        </div>

                    @else

                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-gray-300">

                                <thead class="table-header">

                                    <tr>

                                        <th>
                                            ID
                                        </th>

                                        <th>
                                            Servicio
                                        </th>

                                        <th>
                                            Precio
                                        </th>

                                        <th>
                                            Impuestos %
                                        </th>

                                        <th>
                                            Día de vencimiento
                                        </th>

                                        <th>
                                            Precio vencido
                                        </th>

                                        <th>
                                            Período
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

                                    @foreach($services as $service)

                                        <tr class="hover:bg-gray-50">

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                {{ $service->id }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 font-medium text-[21px]">
                                                {{ $service->service }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                ${{ number_format($service->price, 2, ',', '.') }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                {{ number_format($service->tax_percentage ?? 0, 2, ',', '.') }}%
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                Día {{ $service->due_day }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                ${{ number_format($service->overdue_price, 2, ',', '.') }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                {{ $service->period }}
                                                {{ $service->period == 1 ? 'mes' : 'meses' }}
                                            </td>

                                            <td class="px-6 py-5 text-gray-900 text-[21px]">
                                                {{ $service->created_at->format('d/m/Y H:i') }}
                                            </td>

                                            <td class="px-6 py-5 text-[21px]">

                                                <div class="row-actions">

                                                    <a
                                                        href="{{ route('admin.services.edit', $service) }}"
                                                        class="btn"
                                                    >
                                                        Editar
                                                    </a>

                                                    <form
                                                        action="{{ route('admin.services.destroy', $service) }}"
                                                        method="POST"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button
                                                            type="submit"
                                                            onclick="return confirm('¿Eliminar este servicio?')"
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

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-app-layout>