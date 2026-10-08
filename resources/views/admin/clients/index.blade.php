<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Clientes
        </h2>

    </x-slot>


    <style>
        /*
         * Botones: misma medida para <a> y <button>
         * (mismo alto, mismo tamaño de letra, misma alineación).
         * Medidas tomadas del dashboard general.
         */
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
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563; /* ring-gray-600 + offset */
        }

        .btn-secondary {
            background-color: #ffffff;
            border: 1px solid #9ca3af;
            color: #111827;
        }

        /*
         * ==========================================================
         * TARJETAS DE CLIENTES (2 renglones por tarjeta)
         * ==========================================================
         */

        .client-list {
            width: 100%;
            margin: 0 auto 40px auto;
        }

        .client-card {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            padding: 24px;
            margin-bottom: 10px;
        }

        .client-card-content {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 30px;
        }

        .client-data {
            flex: 1;
            min-width: 0;
        }

        .client-row-1,
        .client-row-2 {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 20px;
        }

        .client-row-2 {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .span-2 {
            grid-column: span 2;
        }

        .client-field {
            min-width: 0;
        }

        .client-label {
            font-size: 18px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .client-value {
            font-size: 21px;
            font-weight: 600;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .client-value-normal {
            font-size: 21px;
            font-weight: 500;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .saldo-debe {
            color: #b91c1c;
        }

        .saldo-cero {
            color: #15803d;
        }

        .client-actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            min-width: 160px;
            border-left: 1px solid #e5e7eb;
            padding-left: 25px;
        }

        .client-actions .btn,
        .client-actions form {
            width: 100%;
        }

        .client-actions form {
            margin: 0;
        }

        .client-empty {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            color: #111827;
            font-size: 21px;
        }

        .notice-card {
            position: relative;
            margin-bottom: 32px;
            padding: 24px 64px 24px 24px;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-left: 8px solid #b45309;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            color: #111827;
            font-size: 21px;
        }

        .notice-card strong {
            display: block;
            margin-bottom: 6px;
            font-size: 24px;
        }

        .notice-close {
            position: absolute;
            top: 12px;
            right: 14px;
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            font-size: 28px;
            line-height: 1;
            color: #6b7280;
            cursor: pointer;
        }

        .notice-close:hover {
            color: #111827;
        }

        @media (max-width: 1100px) {
            .client-row-1,
            .client-row-2 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .client-card-content {
                flex-direction: column;
                gap: 20px;
            }

            .client-row-1,
            .client-row-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .client-actions {
                border-left: none;
                border-top: 1px solid #e5e7eb;
                padding-left: 0;
                padding-top: 20px;
                flex-direction: row;
                flex-wrap: wrap;
                min-width: auto;
            }
        }

        @media (max-width: 500px) {
            .client-row-1,
            .client-row-2 {
                grid-template-columns: 1fr;
            }

            .span-2 {
                grid-column: span 1;
            }

            .client-actions {
                flex-direction: column;
            }
        }
    </style>


    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            {{-- ================================================== --}}
            {{-- Mensaje de éxito --}}
            {{-- ================================================== --}}

            @if (session('success'))

                <div
                    class="mb-8 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
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
                    <ul class="list-disc list-inside space-y-1 text-[21px]">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>
                </div>

            @endif


            {{-- ================================================== --}}
            {{-- Aviso: cliente bloqueado --}}
            {{-- ================================================== --}}

            @if (session('blocked_client'))

                <div class="notice-card" id="blocked-notice" role="status">

                    <button
                        type="button"
                        class="notice-close"
                        aria-label="Cerrar aviso"
                        onclick="document.getElementById('blocked-notice').remove();"
                    >
                        &times;
                    </button>

                    <strong>Cliente bloqueado</strong>

                    El cliente «{{ session('blocked_client')['name'] }}» fue
                    bloqueado. Sus datos solo se pueden ver desde la base de
                    datos y se borrarán definitivamente el
                    {{ session('blocked_client')['delete_on'] }}
                    (en 5 años). Sus facturas no se modifican.

                </div>

            @endif


            {{-- ================================================== --}}
            {{-- Acciones --}}
            {{-- ================================================== --}}

            <div class="mb-8 flex items-center justify-between">

                <h3 class="font-semibold text-white text-[32px]">
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
                                    class="block font-semibold text-gray-900 text-[21px]"
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
                                           text-[21px]
                                           shadow-sm
                                           focus:border-indigo-600
                                           focus:ring-indigo-600"
                                    style="height: 42px;"
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
            {{-- Listado de clientes --}}
            {{-- ================================================== --}}

            @php
                $condicionesIva = [
                    1  => 'IVA Responsable Inscripto',
                    4  => 'IVA Sujeto Exento',
                    5  => 'Consumidor Final',
                    6  => 'Responsable Monotributo',
                    7  => 'Sujeto No Categorizado',
                    8  => 'Proveedor del Exterior',
                    9  => 'Cliente del Exterior',
                    10 => 'IVA Liberado – Ley N° 19.640',
                    13 => 'Monotributista Social',
                    15 => 'IVA No Alcanzado',
                    16 => 'Monotributo Trabajador Independiente Promovido',
                ];
            @endphp

            <div class="client-list">

                @forelse ($clients as $client)

                    @php
                        $codigoIva = $client->arca_iva_condition ?: 5;
                        $saldo = (float) $client->saldo_por_cobrar;
                    @endphp

                    <div class="client-card">

                        <div class="client-card-content">

                            <div class="client-data">

                                {{-- Renglón 1 --}}
                                <div class="client-row-1">

                                    <div class="client-field">
                                        <div class="client-label">Cliente</div>
                                        <div class="client-value">{{ $client->name }}</div>
                                        <div class="client-value-normal">
                                            DNI {{ $client->document }}
                                        </div>
                                    </div>

                                    <div class="client-field">
                                        <div class="client-label">CUIT</div>
                                        <div class="client-value-normal">
                                            {{ $client->cuit ?: 'N/A' }}
                                        </div>
                                    </div>

                                    <div class="client-field">
                                        <div class="client-label">Condición frente al IVA</div>
                                        <div class="client-value-normal">
                                            {{ $codigoIva }}
                                            @if (isset($condicionesIva[$codigoIva]))
                                                — {{ $condicionesIva[$codigoIva] }}
                                            @endif
                                        </div>
                                    </div>

                                    <div class="client-field">
                                        <div class="client-label">Saldo por cobrar</div>
                                        <div class="client-value {{ $saldo > 0 ? 'saldo-debe' : 'saldo-cero' }}">
                                            ${{ number_format($saldo, 2, ',', '.') }}
                                        </div>
                                    </div>

                                    <div class="client-field">
                                        <div class="client-label">Creado</div>
                                        <div class="client-value-normal">
                                            {{ $client->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>

                                </div>

                                {{-- Renglón 2: contacto --}}
                                <div class="client-row-2">

                                    <div class="client-field">
                                        <div class="client-label">Teléfono</div>
                                        <div class="client-value-normal">
                                            {{ $client->phone ?: 'N/A' }}
                                        </div>
                                    </div>

                                    <div class="client-field span-2">
                                        <div class="client-label">Email</div>
                                        <div class="client-value-normal">
                                            {{ $client->email ?: 'N/A' }}
                                        </div>
                                    </div>

                                    <div class="client-field span-2">
                                        <div class="client-label">Dirección</div>
                                        <div class="client-value-normal">
                                            {{ $client->address ?: 'N/A' }}
                                        </div>
                                    </div>

                                </div>

                            </div>

                            {{-- Acciones --}}
                            <div class="client-actions">

                                <a href="{{ route('admin.clients.show', $client) }}" class="btn">
                                    Ver
                                </a>

                                <a href="{{ route('admin.clients.edit', $client) }}" class="btn">
                                    Editar
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('admin.clients.destroy', $client) }}"
                                    onsubmit="return confirm(
                                        '¿Bloquear este cliente? Sus datos se conservarán 5 años (solo visibles desde la base de datos) y luego se borrarán.'
                                    );"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn">
                                        Bloquear
                                    </button>
                                </form>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="client-empty">
                        No hay clientes registrados.
                    </div>

                @endforelse

            </div>


            {{-- Paginación --}}
            <div class="mt-8">
                {{ $clients->links() }}
            </div>

        </div>

    </div>

</x-app-layout>