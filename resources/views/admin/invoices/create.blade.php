<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Nueva factura manual
        </h2>
    </x-slot>

    <style>
        .card-header {
            background-color: #111827;
            padding: 20px 24px;
        }

        .card-header h3 {
            color: #fff;
            font-weight: 600;
            font-size: 24px;
            margin: 0;
        }

        .card-header p {
            color: #d1d5db;
            font-size: 18px;
            margin: 8px 0 0;
        }

        .btn {
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 8px 16px;
            background: #111827;
            border: 1px solid #111827;
            border-radius: 6px;
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #374151;
        }

        .field-label {
            display: block;
            color: #111827;
            font-size: 18px;
            font-weight: 600;
        }

        .field-input {
            display: block;
            width: 100%;
            margin-top: 8px;
            padding: 10px 12px;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            background: #fff;
            color: #111827;
            font-size: 18px;
        }

        .field-input:focus {
            border-color: #4f46e5;
            outline: none;
            box-shadow: 0 0 0 2px #c7d2fe;
        }

        .field-input:disabled {
            background: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }

        .field-help {
            margin-top: 6px;
            color: #4b5563;
            font-size: 15px;
        }

        .field-error {
            margin-top: 6px;
            color: #b91c1c;
            font-size: 16px;
        }

        .section-title {
            margin-bottom: 8px;
            color: #111827;
            font-size: 23px;
            font-weight: 700;
        }

        .section-help {
            margin-bottom: 18px;
            color: #4b5563;
            font-size: 16px;
        }

        .ac-wrap {
            position: relative;
        }

        .ac-list {
            position: absolute;
            top: 100%;
            right: 0;
            left: 0;
            z-index: 30;
            max-height: 300px;
            overflow-y: auto;
            margin: 4px 0 0;
            padding: 0;
            list-style: none;
            background: #fff;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            box-shadow: 0 8px 16px rgb(0 0 0 / 15%);
        }

        .ac-list[hidden] {
            display: none;
        }

        .ac-item {
            padding: 10px 14px;
            color: #111827;
            font-size: 17px;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
        }

        .ac-item:last-child {
            border-bottom: 0;
        }

        .ac-item small {
            display: block;
            margin-top: 3px;
            color: #4b5563;
            font-size: 14px;
        }

        .ac-item.is-active,
        .ac-item:hover {
            background: #f3f4f6;
        }

        .hidden {
            display: none !important;
        }
    </style>

    @php
        $field = 'field-input';
        $label = 'field-label';
        $hint = 'field-help';
        $err = 'field-error';

        $condicionSeleccionada = (string) old('client_iva_condition', 5);
    @endphp

    <div class="py-12">
        <div class="mx-auto" style="width: 90vw;">

            @if ($errors->any())
                <div class="mb-8 rounded-lg bg-red-700 border border-red-800
                            text-white px-6 py-4 shadow-sm">
                    <ul class="list-disc list-inside space-y-1 text-[18px]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="card-header">
                    <h3>Cargar factura histórica</h3>
                    <p>
                        Registrá una factura anterior al uso del sistema.
                        Si seleccionás un cliente o servicio existente, sus datos
                        se completarán automáticamente.
                    </p>
                </div>

                <div class="p-6">

                    <div class="mb-8 rounded-lg border border-gray-400 bg-gray-100
                                px-6 py-5 text-gray-900">
                        <p class="font-semibold text-[18px]">
                            Sobre la fecha informada a ARCA
                        </p>

                        <ul class="mt-3 list-disc list-inside space-y-2 text-[16px]">
                            <li>
                                La fecha histórica se conserva en el sistema.
                            </li>
                            <li>
                                Para emitir el comprobante fiscal, la integración
                                utiliza la fecha de emisión admitida por ARCA.
                            </li>
                            <li>
                                El PDF debe distinguir la fecha histórica de los
                                datos del comprobante autorizado.
                            </li>
                        </ul>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.invoices.store') }}"
                        class="space-y-7"
                        autocomplete="off"
                    >
                        @csrf

                        {{-- Fecha de emisión --}}
                        <div>
                            <label for="issued_at" class="{{ $label }}">
                                Fecha histórica de emisión
                            </label>

                            <input
                                id="issued_at"
                                name="issued_at"
                                type="date"
                                value="{{ old('issued_at') }}"
                                required
                                class="{{ $field }}"
                            >

                            @error('issued_at')
                                <p class="{{ $err }}">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cliente --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div>
                                <label for="client_name" class="{{ $label }}">
                                    Nombre o razón social del cliente
                                </label>

                                <div class="ac-wrap">
                                    <input
                                        id="client_name"
                                        name="client_name"
                                        type="text"
                                        value="{{ old('client_name') }}"
                                        maxlength="255"
                                        required
                                        class="{{ $field }}"
                                    >

                                    <ul id="clientNameList" class="ac-list" hidden></ul>
                                </div>

                                @error('client_name')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="client_document" class="{{ $label }}">
                                    DNI o documento de referencia del sistema
                                </label>

                                <div class="ac-wrap">
                                    <input
                                        id="client_document"
                                        name="client_document"
                                        type="number"
                                        value="{{ old('client_document') }}"
                                        min="1"
                                        required
                                        class="{{ $field }}"
                                    >

                                    <ul id="clientDocList" class="ac-list" hidden></ul>
                                </div>

                                <p class="{{ $hint }}">
                                    Se utiliza para identificar al cliente dentro
                                    de la aplicación.
                                </p>

                                @error('client_document')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p id="clientStatus" class="{{ $hint }}">
                            Escribí el nombre o el documento y seleccioná al
                            cliente si aparece en la lista.
                        </p>

                        {{-- CUIT nacional --}}
                        <div>
                            <label for="client_cuit" class="{{ $label }}">
                                CUIT argentino (opcional)
                            </label>

                            <input
                                id="client_cuit"
                                name="client_cuit"
                                type="text"
                                value="{{ old('client_cuit') }}"
                                inputmode="numeric"
                                maxlength="11"
                                pattern="[0-9]{11}"
                                placeholder="Ej. 20123456789"
                                class="{{ $field }}"
                            >

                            <p class="{{ $hint }}">
                                No uses este campo para ingresar el identificador
                                fiscal extranjero.
                            </p>

                            @error('client_cuit')
                                <p class="{{ $err }}">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Condición fiscal --}}
                        <div>
                            <label for="client_iva_condition" class="{{ $label }}">
                                Condición frente al IVA
                            </label>

                            <select
                                id="client_iva_condition"
                                name="client_iva_condition"
                                required
                                class="{{ $field }}"
                            >
                                @foreach ($condicionesIva as $code => $name)
                                    <option
                                        value="{{ $code }}"
                                        @selected($condicionSeleccionada === (string) $code)
                                    >
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="{{ $hint }}">
                                Para identificar a un comprador extranjero, elegí
                                el código 9 — Cliente del Exterior.
                            </p>

                            @error('client_iva_condition')
                                <p class="{{ $err }}">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Datos de exportación --}}
                        <div
                            id="exportFields"
                            class="{{ $condicionSeleccionada === '9' ? '' : 'hidden' }}
                                   rounded-lg border border-indigo-200 bg-indigo-50 p-5 space-y-6"
                        >
                            <div>
                                <h3 class="section-title">
                                    Datos fiscales del comprador extranjero
                                </h3>

                                <p class="section-help">
                                    Estos datos se guardan como una copia histórica
                                    en la factura y se utilizan al emitir una Factura E.
                                </p>
                            </div>

                            <div>
                                <label for="client_address" class="{{ $label }}">
                                    Domicilio del comprador extranjero
                                </label>

                                <input
                                    id="client_address"
                                    name="client_address"
                                    type="text"
                                    value="{{ old('client_address') }}"
                                    maxlength="300"
                                    class="{{ $field }}"
                                >

                                @error('client_address')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="client_arca_destination_country"
                                    class="{{ $label }}"
                                >
                                    Código de país de destino según ARCA
                                </label>

                                <input
                                    id="client_arca_destination_country"
                                    name="client_arca_destination_country"
                                    type="number"
                                    value="{{ old('client_arca_destination_country') }}"
                                    min="1"
                                    max="999"
                                    step="1"
                                    class="{{ $field }}"
                                >

                                @error('client_arca_destination_country')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="client_arca_cuit_pais_cliente"
                                    class="{{ $label }}"
                                >
                                    Identificador fiscal extranjero
                                    (código fiscal del país)
                                </label>

                                <input
                                    id="client_arca_cuit_pais_cliente"
                                    name="client_arca_cuit_pais_cliente"
                                    type="text"
                                    value="{{ old('client_arca_cuit_pais_cliente') }}"
                                    inputmode="numeric"
                                    maxlength="11"
                                    pattern="[0-9]{1,11}"
                                    class="{{ $field }}"
                                >

                                @error('client_arca_cuit_pais_cliente')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="client_arca_id_impositivo"
                                    class="{{ $label }}"
                                >
                                    ID impositivo extranjero (alternativo)
                                </label>

                                <input
                                    id="client_arca_id_impositivo"
                                    name="client_arca_id_impositivo"
                                    type="text"
                                    value="{{ old('client_arca_id_impositivo') }}"
                                    maxlength="50"
                                    class="{{ $field }}"
                                >

                                <p class="{{ $hint }}">
                                    Debés informar al menos uno de los dos
                                    identificadores fiscales anteriores.
                                </p>

                                @error('client_arca_id_impositivo')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Servicio --}}
                        <div>
                            <label for="service_name" class="{{ $label }}">
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
                                    id="service_name"
                                    name="service_name"
                                    type="text"
                                    value="{{ old('service_name') }}"
                                    maxlength="255"
                                    required
                                    class="{{ $field }}"
                                >

                                <ul id="serviceList" class="ac-list" hidden></ul>
                            </div>

                            <p id="serviceStatus" class="{{ $hint }}">
                                Escribí el nombre y seleccioná el servicio si existe.
                                Si es una factura histórica, también podés ingresarlo
                                manualmente.
                            </p>

                            @error('service_id')
                                <p class="{{ $err }}">{{ $message }}</p>
                            @enderror

                            @error('service_name')
                                <p class="{{ $err }}">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Datos económicos --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="price" class="{{ $label }}">
                                    Precio
                                </label>

                                <input
                                    id="price"
                                    name="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value="{{ old('price') }}"
                                    required
                                    class="{{ $field }}"
                                >

                                @error('price')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="overdue_price" class="{{ $label }}">
                                    Precio vencido
                                </label>

                                <input
                                    id="overdue_price"
                                    name="overdue_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value="{{ old('overdue_price') }}"
                                    required
                                    class="{{ $field }}"
                                >

                                @error('overdue_price')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="tax_percentage" class="{{ $label }}">
                                    IVA (%)
                                </label>

                                <input
                                    id="tax_percentage"
                                    name="tax_percentage"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value="{{ old('tax_percentage') }}"
                                    placeholder="Ej. 21"
                                    required
                                    class="{{ $field }}"
                                >

                                @error('tax_percentage')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="due_date" class="{{ $label }}">
                                Fecha de vencimiento
                            </label>

                            <input
                                id="due_date"
                                name="due_date"
                                type="date"
                                value="{{ old('due_date') }}"
                                required
                                class="{{ $field }}"
                            >

                            @error('due_date')
                                <p class="{{ $err }}">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Estado de pago --}}
                        <div>
                            <label for="payment_status" class="{{ $label }}">
                                Estado de la factura
                            </label>

                            <select
                                id="payment_status"
                                name="payment_status"
                                class="{{ $field }}"
                            >
                                <option
                                    value="pending"
                                    @selected(old('payment_status', 'pending') === 'pending')
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="paid"
                                    @selected(old('payment_status') === 'paid')
                                >
                                    Pagada
                                </option>
                            </select>

                            @error('payment_status')
                                <p class="{{ $err }}">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Datos del pago --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="amount_paid" class="{{ $label }}">
                                    Importe pagado
                                </label>

                                <input
                                    id="amount_paid"
                                    name="amount_paid"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value="{{ old('amount_paid') }}"
                                    class="{{ $field }}"
                                >

                                <p class="{{ $hint }}">
                                    Obligatorio cuando el estado es Pagada.
                                </p>

                                @error('amount_paid')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="paid_at" class="{{ $label }}">
                                    Fecha de pago
                                </label>

                                <input
                                    id="paid_at"
                                    name="paid_at"
                                    type="date"
                                    value="{{ old('paid_at') }}"
                                    class="{{ $field }}"
                                >

                                @error('paid_at')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payment_method" class="{{ $label }}">
                                    Método de pago
                                </label>

                                <input
                                    id="payment_method"
                                    name="payment_method"
                                    type="text"
                                    value="{{ old('payment_method') }}"
                                    maxlength="255"
                                    placeholder="Ej. efectivo"
                                    class="{{ $field }}"
                                >

                                @error('payment_method')
                                    <p class="{{ $err }}">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-4">
                            <a href="{{ route('admin.dashboard') }}" class="btn">
                                Volver al panel
                            </a>

                            <button type="submit" class="btn">
                                Cargar factura
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const clientsUrl = @json(route('admin.invoices.generate-manual.clients'));
        const servicesUrl = @json(route('admin.invoices.generate-manual.services'));

        const $ = (id) => document.getElementById(id);

        /*
         * Cada autocompletado mantiene su propio estado, temporizador
         * y AbortController para evitar interferencias entre listas.
         */
        function attachAutocomplete({
            input,
            list,
            url,
            render,
            onPick,
            onType
        }) {
            let timer = null;
            let controller = null;
            let sequence = 0;
            let items = [];
            let active = -1;

            function close() {
                list.hidden = true;
                list.innerHTML = '';
                items = [];
                active = -1;
            }

            function cancelPending() {
                clearTimeout(timer);
                sequence++;

                if (controller) {
                    controller.abort();
                    controller = null;
                }
            }

            function highlight() {
                Array.from(list.children).forEach((item, index) => {
                    item.classList.toggle('is-active', index === active);
                });
            }

            function pick(item) {
                cancelPending();
                onPick(item);
                close();
            }

            function paint() {
                list.innerHTML = '';

                items.forEach((item) => {
                    const element = document.createElement('li');
                    element.className = 'ac-item';
                    render(item, element);

                    element.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        pick(item);
                    });

                    list.appendChild(element);
                });

                list.hidden = items.length === 0;
            }

            async function search() {
                const query = input.value.trim();

                if (query === '') {
                    close();
                    return;
                }

                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();
                const currentSequence = ++sequence;

                try {
                    const response = await fetch(
                        url + '?q=' + encodeURIComponent(query),
                        {
                            headers: {
                                'Accept': 'application/json'
                            },
                            signal: controller.signal
                        }
                    );

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    const data = await response.json();

                    if (currentSequence !== sequence) {
                        return;
                    }

                    items = Array.isArray(data) ? data : [];
                    active = -1;
                    paint();
                } catch (error) {
                    if (
                        error.name !== 'AbortError'
                        && currentSequence === sequence
                    ) {
                        close();
                    }
                }
            }

            input.addEventListener('input', function () {
                if (onType) {
                    onType();
                }

                clearTimeout(timer);
                timer = setTimeout(search, 250);
            });

            input.addEventListener('keydown', function (event) {
                if (list.hidden || items.length === 0) {
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    active = (active + 1) % items.length;
                    highlight();
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    active = (active - 1 + items.length) % items.length;
                    highlight();
                } else if (event.key === 'Enter' && active >= 0) {
                    event.preventDefault();
                    pick(items[active]);
                } else if (event.key === 'Escape') {
                    cancelPending();
                    close();
                }
            });

            input.addEventListener('blur', function () {
                cancelPending();
                close();
            });
        }

        /*
         * Datos fiscales extranjeros: el bloque solo se muestra
         * cuando la condición fiscal del receptor es el código 9.
         */
        const ivaSelect = $('client_iva_condition');
        const exportFields = $('exportFields');
        const addressField = $('client_address');
        const countryField = $('client_arca_destination_country');

        function syncExportFields() {
            const isExport = ivaSelect.value === '9';

            exportFields.classList.toggle('hidden', !isExport);

            addressField.required = isExport;
            countryField.required = isExport;
        }

        ivaSelect.addEventListener('change', syncExportFields);
        syncExportFields();

        /*
         * Clientes: además de los datos nacionales, completamos
         * dirección, país e identificadores fiscales de exportación.
         */
        const clientStatus = $('clientStatus');
        const defaultClientStatus = clientStatus.textContent.trim();

        function pickClient(client) {
            $('client_name').value = client.name || '';
            $('client_document').value = client.document || '';
            $('client_cuit').value = client.cuit || '';

            ivaSelect.value = String(client.iva_code || 5);

            $('client_address').value = client.address || '';

            $('client_arca_destination_country').value =
                client.destination_country ?? '';

            $('client_arca_cuit_pais_cliente').value =
                client.cuit_pais_cliente || '';

            $('client_arca_id_impositivo').value =
                client.id_impositivo || '';

            syncExportFields();

            clientStatus.textContent =
                'Cliente existente seleccionado. Revisá los datos completados antes de guardar.';
        }

        function renderClient(client, element) {
            element.appendChild(
                document.createTextNode(
                    (client.name || 'Sin nombre')
                    + ' — documento '
                    + (client.document || 'no informado')
                )
            );

            const detail = document.createElement('small');
            const details = [];

            if (client.cuit) {
                details.push('CUIT ' + client.cuit);
            }

            if (client.iva_condition) {
                details.push(client.iva_condition);
            }

            if (client.destination_country) {
                details.push('País de destino ' + client.destination_country);
            }

            detail.textContent = details.length
                ? details.join(' · ')
                : 'Sin datos fiscales adicionales';

            element.appendChild(detail);
        }

        function clientTyped() {
            clientStatus.textContent = defaultClientStatus;
        }

        attachAutocomplete({
            input: $('client_name'),
            list: $('clientNameList'),
            url: clientsUrl,
            render: renderClient,
            onPick: pickClient,
            onType: clientTyped
        });

        attachAutocomplete({
            input: $('client_document'),
            list: $('clientDocList'),
            url: clientsUrl,
            render: renderClient,
            onPick: pickClient,
            onType: clientTyped
        });

        /*
         * Servicios: al seleccionar uno se completan sus datos.
         * Si se modifica su nombre, se elimina la asociación por ID.
         */
        const serviceStatus = $('serviceStatus');
        const defaultServiceStatus = serviceStatus.textContent.trim();

        function pickService(service) {
            $('service_name').value = service.name || '';
            $('service_id').value = service.id || '';
            $('price').value = service.price ?? '';
            $('overdue_price').value = service.overdue_price ?? '';
            $('tax_percentage').value = service.tax_percentage ?? '';

            serviceStatus.textContent =
                'Servicio existente seleccionado. Podés revisar los importes antes de guardar.';
        }

        attachAutocomplete({
            input: $('service_name'),
            list: $('serviceList'),
            url: servicesUrl,
            render: function (service, element) {
                element.appendChild(
                    document.createTextNode(service.name || '')
                );

                const detail = document.createElement('small');

                detail.textContent =
                    'Precio: $' + service.price
                    + ' · Vencido: $' + service.overdue_price
                    + ' · IVA: ' + service.tax_percentage + '%';

                element.appendChild(detail);
            },
            onPick: pickService,
            onType: function () {
                $('service_id').value = '';
                serviceStatus.textContent = defaultServiceStatus;
            }
        });

        /*
         * Datos de pago: si el estado es Pendiente, estos campos se
         * deshabilitan. Para Pagada, el importe se vuelve obligatorio.
         */
        const statusSelect = $('payment_status');
        const amountPaid = $('amount_paid');
        const paidAt = $('paid_at');
        const paymentMethod = $('payment_method');

        function syncPaymentFields() {
            const isPaid = statusSelect.value === 'paid';

            [amountPaid, paidAt, paymentMethod].forEach((field) => {
                field.disabled = !isPaid;
            });

            amountPaid.required = isPaid;
        }

        statusSelect.addEventListener('change', syncPaymentFields);
        syncPaymentFields();
    });
    </script>
</x-app-layout>