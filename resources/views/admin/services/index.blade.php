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
            max-height: 340px;
            overflow-y: auto;
        }

        .ac-list[hidden] {
            display: none;
        }

        .ac-item {
            padding: 10px 14px;
            font-size: 21px;
            color: #111827;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
        }

        .ac-item:last-child {
            border-bottom: 0;
        }

        .ac-item small {
            display: block;
            font-size: 18px;
            color: #4b5563;
        }

        .ac-item.is-active,
        .ac-item:hover {
            background-color: #f3f4f6;
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

                <div class="flex items-center gap-4">

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="btn"
                    >
                        Volver al panel
                    </a>

                    <a
                        href="{{ route('admin.services.create') }}"
                        class="btn"
                    >
                        Crear servicio
                    </a>

                </div>

            </div>


            {{-- Buscador --}}
            <div class="mb-8 bg-white border border-gray-300 rounded-lg shadow-sm">

                <div class="p-6">

                    <form
                        method="GET"
                        action="{{ route('admin.services.index') }}"
                    >

                        <div class="flex flex-col lg:flex-row gap-4">

                            <div class="flex-1">

                                <label
                                    for="search"
                                    class="block font-semibold text-gray-900 text-[21px]"
                                >
                                    Buscar servicio
                                </label>

                                <div class="ac-wrap">

                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        value="{{ request('search') }}"
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

                                    <ul id="searchList" class="ac-list" hidden></ul>

                                </div>

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
                                        href="{{ route('admin.services.index') }}"
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


            {{-- Tabla --}}
            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="p-6">

                    @if($services->isEmpty())

                        <div class="py-10 text-center">

                            <p class="text-gray-800 text-[21px]">
                                {{ request('search')
                                    ? 'No se encontró ningún servicio con esa búsqueda.'
                                    : 'No hay servicios registrados.' }}
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

    <script src="{{ asset('js/search-dropdown.js') }}"></script>
    <script>
        const money = new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
        });

        searchDropdown({
            inputId: 'search',
            listId: 'searchList',
            sources: [{
                url: @json(route('admin.invoices.generate-manual.services')),
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
        });
    </script>

</x-app-layout>