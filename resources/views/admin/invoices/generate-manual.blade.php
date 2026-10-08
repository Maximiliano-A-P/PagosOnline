<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Generar factura manual
        </h2>
    </x-slot>


    <style>
        .card-header {
            background-color: #111827;
            padding: 20px 24px;
        }

        .card-header h3 {
            color: #ffffff;
            font-weight: 600;
            font-size: 24px;
            margin: 0;
        }

        .card-header p {
            color: #d1d5db;
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
            background-color: #111827;
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
            background-color: #374151;
        }

        .btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .btn-secondary {
            background-color: #ffffff;
            border: 1px solid #9ca3af;
            color: #111827;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6;
        }

        .search-input {
            box-sizing: border-box;
            height: 42px;
        }

        .match-list {
            list-style: none;
            margin: 12px 0 0 0;
            padding: 0;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
        }

        .match-list:empty {
            display: none;
        }

        .match-item {
            display: block;
            width: 100%;
            padding: 10px 16px;
            text-align: left;
            background-color: #ffffff;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            font-family: inherit;
            font-size: 21px;
            color: #111827;
            cursor: pointer;
        }

        .match-list li:last-child .match-item {
            border-bottom: none;
        }

        .match-item:hover,
        .match-item:focus {
            background-color: #f3f4f6;
            outline: none;
        }

        .match-item small {
            display: block;
            color: #6b7280;
            font-size: 18px;
        }

        .match-empty {
            margin-top: 12px;
            color: #6b7280;
            font-size: 21px;
        }

        .selected-box {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .selected-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .info-label {
            font-size: 18px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 21px;
            font-weight: 500;
            color: #111827;
            overflow-wrap: anywhere;
        }

        @media (max-width: 700px) {
            .selected-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>


    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            @if (session('error'))
                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('error') }}
                </div>
            @endif

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


            <form
                id="manual-form"
                method="POST"
                action="{{ route('admin.invoices.generate-manual.store') }}"
            >
                @csrf

                <input type="hidden" name="client_id" id="client_id">
                <input type="hidden" name="service_id" id="service_id">


                {{-- ================================================== --}}
                {{-- Tarjeta 1: cliente --}}
                {{-- ================================================== --}}

                <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden mb-10">

                    <div class="card-header">
                        <h3>1. Cliente</h3>
                        <p>Buscá por nombre o documento.</p>
                    </div>

                    <div class="p-6">

                        <div id="client-search-box">

                            <label
                                for="client-input"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                Buscar cliente
                            </label>

                            <input
                                id="client-input"
                                type="text"
                                autocomplete="off"
                                placeholder="Nombre o documento"
                                class="search-input mt-2 block w-full rounded-md
                                       border-gray-400 bg-white text-gray-900
                                       text-[21px] shadow-sm
                                       focus:border-indigo-600 focus:ring-indigo-600"
                            >

                            <ul id="client-matches" class="match-list"></ul>
                            <div id="client-empty" class="match-empty" hidden>
                                No se encontraron clientes.
                            </div>

                        </div>

                        <div id="client-selected" class="selected-box" hidden>

                            <div class="selected-grid" id="client-info"></div>

                            <div class="mt-6">
                                <button type="button" id="client-change" class="btn btn-secondary">
                                    Cambiar cliente
                                </button>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================== --}}
                {{-- Tarjeta 2: servicio --}}
                {{-- ================================================== --}}

                <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden mb-10">

                    <div class="card-header">
                        <h3>2. Servicio</h3>
                        <p>Buscá por nombre.</p>
                    </div>

                    <div class="p-6">

                        <div id="service-search-box">

                            <label
                                for="service-input"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                Buscar servicio
                            </label>

                            <input
                                id="service-input"
                                type="text"
                                autocomplete="off"
                                placeholder="Nombre del servicio"
                                class="search-input mt-2 block w-full rounded-md
                                       border-gray-400 bg-white text-gray-900
                                       text-[21px] shadow-sm
                                       focus:border-indigo-600 focus:ring-indigo-600"
                            >

                            <ul id="service-matches" class="match-list"></ul>
                            <div id="service-empty" class="match-empty" hidden>
                                No se encontraron servicios.
                            </div>

                        </div>

                        <div id="service-selected" class="selected-box" hidden>

                            <div class="selected-grid" id="service-info"></div>

                            <div class="mt-6">
                                <button type="button" id="service-change" class="btn btn-secondary">
                                    Cambiar servicio
                                </button>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- ================================================== --}}
                {{-- Botones --}}
                {{-- ================================================== --}}

                <div class="flex items-center justify-end gap-4">

                    <a href="{{ route('admin.dashboard') }}" class="btn">
                        Volver al panel
                    </a>

                    <button type="submit" id="submit-button" class="btn" disabled>
                        Generar factura
                    </button>

                </div>

            </form>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const clientsUrl  = @json(route('admin.invoices.generate-manual.clients'));
            const servicesUrl = @json(route('admin.invoices.generate-manual.services'));

            const form   = document.getElementById('manual-form');
            const submit = document.getElementById('submit-button');

            const money = new Intl.NumberFormat('es-AR', {
                style: 'currency',
                currency: 'ARS',
            });

            const dash = '—';

            /*
             * Cada buscador tiene su propio estado, su propio contador
             * de pedidos y su propio temporizador: no comparten nada,
             * así una respuesta tardía de uno nunca pisa al otro.
             */
            function createPicker(config) {

                const state = {
                    selected: null,
                    requestId: 0,
                    timer: null,
                };

                const input    = document.getElementById(config.prefix + '-input');
                const matches  = document.getElementById(config.prefix + '-matches');
                const empty    = document.getElementById(config.prefix + '-empty');
                const box      = document.getElementById(config.prefix + '-search-box');
                const selected = document.getElementById(config.prefix + '-selected');
                const info     = document.getElementById(config.prefix + '-info');
                const change   = document.getElementById(config.prefix + '-change');
                const hidden   = document.getElementById(config.prefix === 'client' ? 'client_id' : 'service_id');

                function clearMatches() {
                    matches.innerHTML = '';
                    empty.hidden = true;
                }

                function addInfo(label, value) {
                    const wrap = document.createElement('div');

                    const l = document.createElement('div');
                    l.className = 'info-label';
                    l.textContent = label;

                    const v = document.createElement('div');
                    v.className = 'info-value';
                    v.textContent = (value === null || value === undefined || value === '')
                        ? dash
                        : value;

                    wrap.appendChild(l);
                    wrap.appendChild(v);
                    info.appendChild(wrap);
                }

                function select(item) {
                    state.selected = item;
                    hidden.value = item.id;

                    clearMatches();
                    box.hidden = true;

                    info.innerHTML = '';
                    config.fields(item).forEach(function (f) {
                        addInfo(f[0], f[1]);
                    });
                    selected.hidden = false;

                    config.onChange();
                }

                function unselect() {
                    state.selected = null;
                    hidden.value = '';

                    selected.hidden = true;
                    box.hidden = false;

                    input.value = '';
                    clearMatches();
                    input.focus();

                    config.onChange();
                }

                function render(items) {
                    clearMatches();

                    if (!items.length) {
                        empty.hidden = false;
                        return;
                    }

                    items.forEach(function (item) {
                        const li  = document.createElement('li');
                        const btn = document.createElement('button');

                        btn.type = 'button';
                        btn.className = 'match-item';

                        const title = document.createElement('span');
                        title.textContent = config.title(item);
                        btn.appendChild(title);

                        const sub = config.subtitle(item);
                        if (sub) {
                            const small = document.createElement('small');
                            small.textContent = sub;
                            btn.appendChild(small);
                        }

                        btn.addEventListener('click', function () {
                            select(item);
                        });

                        li.appendChild(btn);
                        matches.appendChild(li);
                    });
                }

                async function search(term) {
                    const id = ++state.requestId;

                    try {
                        const response = await fetch(
                            config.url + '?q=' + encodeURIComponent(term),
                            {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            }
                        );

                        if (!response.ok) throw new Error('HTTP ' + response.status);

                        const items = await response.json();

                        // Respuesta vieja: se descarta.
                        if (id !== state.requestId) return;

                        render(items);

                    } catch (error) {
                        if (id !== state.requestId) return;
                        clearMatches();
                        empty.textContent = 'No se pudo buscar. Intentá de nuevo.';
                        empty.hidden = false;
                    }
                }

                input.addEventListener('input', function () {
                    clearTimeout(state.timer);

                    const term = input.value.trim();

                    if (term === '') {
                        state.requestId++;
                        clearMatches();
                        return;
                    }

                    state.timer = setTimeout(function () {
                        search(term);
                    }, 250);
                });

                change.addEventListener('click', unselect);

                return {
                    isSelected: function () { return state.selected !== null; },
                };
            }


            function updateSubmit() {
                submit.disabled = !(clientPicker.isSelected() && servicePicker.isSelected());
            }


            const clientPicker = createPicker({
                prefix: 'client',
                url: clientsUrl,
                title: function (c) { return c.name; },
                subtitle: function (c) { return 'DNI ' + c.document; },
                fields: function (c) {
                    return [
                        ['Nombre', c.name],
                        ['Documento (DNI)', c.document],
                        ['CUIT', c.cuit],
                        ['Condición frente al IVA', c.iva_condition],
                        ['Teléfono', c.phone],
                        ['Email', c.email],
                        ['Dirección', c.address],
                    ];
                },
                onChange: updateSubmit,
            });

            const servicePicker = createPicker({
                prefix: 'service',
                url: servicesUrl,
                title: function (s) { return s.name; },
                subtitle: function (s) {
                    return money.format(s.price) + ' · cada ' + s.period + ' mes(es)';
                },
                fields: function (s) {
                    return [
                        ['Servicio', s.name],
                        ['Precio (NETO)', money.format(s.price)],
                        ['Precio vencido (NETO)', money.format(s.overdue_price)],
                        ['Impuestos %', s.tax_percentage + '%'],
                        ['Día de vencimiento', s.due_day],
                        ['Período (meses)', s.period],
                    ];
                },
                onChange: updateSubmit,
            });


            // Evita doble envío.
            let submitting = false;

            form.addEventListener('submit', function (event) {
                if (submitting) {
                    event.preventDefault();
                    return;
                }

                submitting = true;
                submit.disabled = true;
                submit.textContent = 'Generando…';
            });

            // Todo inicializado: recién ahora se puede habilitar el envío.
            updateSubmit();
        });
    </script>

</x-app-layout>