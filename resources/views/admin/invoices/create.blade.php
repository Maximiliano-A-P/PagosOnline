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

    /* ---- Autocompletado ---- */
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

    form input:disabled {
        background-color: #f3f4f6;
        color: #6b7280;
        cursor: not-allowed;
    }
</style>


<div class="py-12">

<div class="mx-auto" style="width: 90vw;">

    @php
        $field = 'mt-2 block w-full rounded-md border-gray-400 bg-white text-gray-900 text-[21px] shadow-sm focus:border-indigo-600 focus:ring-indigo-600';
        $label = 'block font-semibold text-gray-900 text-[21px]';
        $hint  = 'mt-2 text-gray-700 text-[21px]';
        $err   = 'mt-2 text-red-700 text-[21px]';
    @endphp

    {{-- Errores --}}
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


    <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

        {{-- Encabezado --}}
        <div class="card-header">

            <h3>
                Cargar factura histórica
            </h3>

            <p>
                Utilizá este formulario para registrar una factura
                que ya existía antes de utilizar el sistema.
                Cliente y servicio pueden existir o no: si coinciden
                con uno cargado, elegilo de la lista y se completan
                los datos; si no, escribilos a mano.
            </p>

        </div>


        <div class="p-6">

            {{-- Nota sobre la fecha informada a ARCA --}}
            <div
                class="mb-8 rounded-lg border border-gray-400 bg-gray-100
                       px-6 py-5 text-gray-900"
            >
                <p class="font-semibold text-[21px]">
                    Sobre la fecha informada a ARCA
                </p>

                <ul class="mt-3 list-disc list-inside space-y-2 text-[21px]">
                    <li>
                        ARCA solo acepta comprobantes de servicios con una
                        fecha de hasta 10 días corridos respecto de hoy.
                    </li>
                    <li>
                        Si la fecha de emisión de esta factura es más
                        antigua, a ARCA se le informa la fecha más antigua
                        que acepta.
                    </li>
                    <li>
                        En el sistema la fecha de emisión se mantiene tal
                        como la cargues.
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
                        Fecha de emisión
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
                            Nombre del cliente
                        </label>

                        <div class="ac-wrap">
                            <input
                                id="client_name"
                                name="client_name"
                                type="text"
                                value="{{ old('client_name') }}"
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
                            DNI
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

                        @error('client_document')
                            <p class="{{ $err }}">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <p id="clientStatus" class="{{ $hint }}" style="margin-top: -12px;">
                    Escribí el nombre o el DNI: si el cliente existe,
                    elegilo de la lista para completar sus datos.
                </p>


                {{-- CUIT (opcional) --}}
                <div>
                    <label for="client_cuit" class="{{ $label }}">
                        CUIT (opcional)
                    </label>

                    <input
                        id="client_cuit"
                        name="client_cuit"
                        type="text"
                        value="{{ old('client_cuit') }}"
                        inputmode="numeric"
                        maxlength="11"
                        pattern="\d{11}"
                        placeholder="Ej. 20123456789"
                        class="{{ $field }}"
                    >

                    <p class="{{ $hint }}">
                        Dejar vacío si el cliente no tiene CUIT.
                    </p>

                    @error('client_cuit')
                        <p class="{{ $err }}">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Condición frente al IVA --}}
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
                                @selected((string) old('client_iva_condition', 5) === (string) $code)
                            >
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="{{ $hint }}">
                        Por defecto: Consumidor Final.
                    </p>

                    @error('client_iva_condition')
                        <p class="{{ $err }}">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Servicio --}}
                <div>
                    <label for="service_name" class="{{ $label }}">
                        Servicio
                    </label>

                    <input type="hidden" id="service_id" name="service_id" value="{{ old('service_id') }}">

                    <div class="ac-wrap">
                        <input
                            id="service_name"
                            name="service_name"
                            type="text"
                            value="{{ old('service_name') }}"
                            required
                            class="{{ $field }}"
                        >
                        <ul id="serviceList" class="ac-list" hidden></ul>
                    </div>

                    <p id="serviceStatus" class="{{ $hint }}">
                        Escribí el nombre: si el servicio existe, elegilo de la
                        lista para completar precios e IVA. Si no existe,
                        cargá los importes a mano.
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
                            placeholder="Ej. 21 (0 si no lleva IVA)"
                            required
                            class="{{ $field }}"
                        >

                        @error('tax_percentage')
                            <p class="{{ $err }}">{{ $message }}</p>
                        @enderror
                    </div>

                </div>


                {{-- Vencimiento --}}
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


                {{-- Estado --}}
                <div>
                    <label for="payment_status" class="{{ $label }}">
                        Estado
                    </label>

                    <select
                        id="payment_status"
                        name="payment_status"
                        class="{{ $field }}"
                    >
                        <option value="pending" @selected(old('payment_status', 'pending') === 'pending')>
                            Pendiente
                        </option>

                        <option value="paid" @selected(old('payment_status') === 'paid')>
                            Pagada
                        </option>
                    </select>

                    @error('payment_status')
                        <p class="{{ $err }}">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Datos del pago (solo si está pagada) --}}
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
                            Obligatorio si la factura está pagada.
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
                            placeholder="Ej. efectivo"
                            class="{{ $field }}"
                        >

                        @error('payment_method')
                            <p class="{{ $err }}">{{ $message }}</p>
                        @enderror
                    </div>

                </div>


                {{-- Botones --}}
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

    const clientsUrl  = @json(route('admin.invoices.generate-manual.clients'));
    const servicesUrl = @json(route('admin.invoices.generate-manual.services'));

    const $ = (id) => document.getElementById(id);

    /*
     * Autocompletado genérico. Cada instancia tiene su propia lista,
     * su propio temporizador y su propio AbortController: nada se
     * comparte entre los buscadores, y una respuesta vieja nunca pisa
     * a una más nueva.
     */
    function attachAutocomplete({ input, list, url, render, onPick, onType }) {

        let timer = null;
        let controller = null;
        let seq = 0;
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
            seq++;
            if (controller) {
                controller.abort();
                controller = null;
            }
        }

        function highlight() {
            Array.from(list.children).forEach((li, i) => {
                li.classList.toggle('is-active', i === active);
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
                const li = document.createElement('li');
                li.className = 'ac-item';
                render(item, li);

                // mousedown (no click): evita que el blur cierre la
                // lista antes de registrar la elección.
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    pick(item);
                });

                list.appendChild(li);
            });

            list.hidden = items.length === 0;
        }

        async function search() {
            const q = input.value.trim();

            if (q === '') {
                close();
                return;
            }

            if (controller) controller.abort();
            controller = new AbortController();
            const mySeq = ++seq;

            try {
                const response = await fetch(
                    url + '?q=' + encodeURIComponent(q),
                    {
                        headers: { 'Accept': 'application/json' },
                        signal: controller.signal,
                    }
                );

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();

                if (mySeq !== seq) return;

                items = Array.isArray(data) ? data : [];
                active = -1;
                paint();

            } catch (e) {
                if (e.name !== 'AbortError' && mySeq === seq) close();
            }
        }

        input.addEventListener('input', function () {
            if (onType) onType();
            clearTimeout(timer);
            timer = setTimeout(search, 250);
        });

        input.addEventListener('keydown', function (e) {
            if (list.hidden) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                active = (active + 1) % items.length;
                highlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                active = (active - 1 + items.length) % items.length;
                highlight();
            } else if (e.key === 'Enter' && active >= 0) {
                e.preventDefault();
                pick(items[active]);
            } else if (e.key === 'Escape') {
                cancelPending();
                close();
            }
        });

        input.addEventListener('blur', function () {
            cancelPending();
            close();
        });
    }


    /* ------------------------------------------------------------
     * Clientes: se busca por nombre o por DNI y al elegir uno se
     * completan nombre, DNI, CUIT y condición de IVA.
     * ------------------------------------------------------------ */
    const clientStatus = $('clientStatus');
    const clientDefaultText = clientStatus.textContent;

    function pickClient(client) {
        $('client_name').value = client.name || '';
        $('client_document').value = client.document || '';
        $('client_cuit').value = client.cuit || '';
        const ivaSelect = $('client_iva_condition');
        ivaSelect.value = String(client.iva_code || 5);

        // Si el código del cliente no está entre las opciones, queda
        // en Consumidor Final.
        if (ivaSelect.value === '') {
            ivaSelect.value = '5';
        }
        clientStatus.textContent =
            'Cliente existente seleccionado: se completaron sus datos (podés ajustarlos).';
    }

    function renderClient(client, li) {
        li.textContent = client.name + ' — DNI ' + client.document;

        const small = document.createElement('small');
        small.textContent = client.cuit
            ? 'CUIT ' + client.cuit
            : 'Sin CUIT cargado';
        li.appendChild(small);
    }

    function clientTyped() {
        clientStatus.textContent = clientDefaultText;
    }

    attachAutocomplete({
        input: $('client_name'),
        list: $('clientNameList'),
        url: clientsUrl,
        render: renderClient,
        onPick: pickClient,
        onType: clientTyped,
    });

    attachAutocomplete({
        input: $('client_document'),
        list: $('clientDocList'),
        url: clientsUrl,
        render: renderClient,
        onPick: pickClient,
        onType: clientTyped,
    });


    /* ------------------------------------------------------------
     * Servicios: si se elige uno existente se completan nombre,
     * precio, precio vencido e IVA y se guarda su id. Si se escribe
     * uno que no existe, el id queda vacío y se carga todo a mano.
     * ------------------------------------------------------------ */
    const serviceStatus = $('serviceStatus');
    const serviceDefaultText = serviceStatus.textContent;

    function pickService(service) {
        $('service_name').value = service.name || '';
        $('service_id').value = service.id || '';
        $('price').value = service.price ?? '';
        $('overdue_price').value = service.overdue_price ?? '';
        $('tax_percentage').value = service.tax_percentage ?? '';
        serviceStatus.textContent =
            'Servicio existente seleccionado: se completaron precios e IVA (podés ajustarlos).';
    }

    attachAutocomplete({
        input: $('service_name'),
        list: $('serviceList'),
        url: servicesUrl,
        render: function (service, li) {
            li.textContent = service.name;

            const small = document.createElement('small');
            small.textContent =
                '$' + service.price + ' · vencido $' + service.overdue_price +
                ' · IVA ' + service.tax_percentage + '%';
            li.appendChild(small);
        },
        onPick: pickService,
        onType: function () {
            // Si el nombre se edita a mano, deja de ser "el servicio
            // existente": se guarda solo como nombre histórico.
            $('service_id').value = '';
            serviceStatus.textContent = serviceDefaultText;
        },
    });


    /* ------------------------------------------------------------
     * Pago: los datos del pago solo se habilitan si está pagada, y
     * en ese caso el importe pagado es obligatorio.
     * ------------------------------------------------------------ */
    const status = $('payment_status');
    const amountPaid = $('amount_paid');
    const paymentFields = [amountPaid, $('paid_at'), $('payment_method')];

    function syncPayment() {
        const paid = status.value === 'paid';

        paymentFields.forEach(function (field) {
            field.disabled = !paid;
        });

        amountPaid.required = paid;
    }

    status.addEventListener('change', syncPayment);
    syncPayment();

});
</script>

</x-app-layout>