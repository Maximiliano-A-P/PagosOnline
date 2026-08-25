<x-app-layout>

```
<x-slot name="header">
    <h2 class="font-semibold text-white leading-tight text-[4vh]">
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
        font-size: 20px;
        margin: 0;
    }

    .card-header p {
        color: #d1d5db;
        font-size: 14px;
        margin: 8px 0 0;
    }

    .btn {
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 14px;
        background-color: #111827;
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
        background-color: #374151;
    }
</style>


<div class="py-12">

    <div class="max-w-4xl mx-auto px-6 lg:px-8">

        {{-- Errores --}}
        @if ($errors->any())

            <div
                class="mb-8 rounded-lg bg-red-700 border border-red-800
                       text-white px-6 py-4 shadow-sm"
            >
                <ul class="list-disc list-inside space-y-1 text-[3vh]">

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
                </p>

            </div>


            <div class="p-6">

                <form
                    method="POST"
                    action="{{ route('admin.invoices.store') }}"
                    class="space-y-7"
                >

                    @csrf


                    {{-- Fecha de emisión --}}
                    <div>

                        <label
                            for="issued_at"
                            class="block font-semibold text-gray-900 text-[3vh]"
                        >
                            Fecha de emisión
                        </label>

                        <input
                            id="issued_at"
                            name="issued_at"
                            type="date"
                            value="{{ old('issued_at') }}"
                            required
                            class="mt-2 block w-full rounded-md
                                   border-gray-400 bg-white
                                   text-gray-900 text-[3vh]
                                   shadow-sm
                                   focus:border-indigo-600
                                   focus:ring-indigo-600"
                        >

                        @error('issued_at')
                            <p class="mt-2 text-red-700 text-[3vh]">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Cliente --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>

                            <label
                                for="client_name"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Nombre del cliente
                            </label>

                            <input
                                id="client_name"
                                name="client_name"
                                type="text"
                                value="{{ old('client_name') }}"
                                required
                                class="mt-2 block w-full rounded-md
                                       border-gray-400 bg-white
                                       text-gray-900 text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            @error('client_name')
                                <p class="mt-2 text-red-700 text-[3vh]">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div>

                            <label
                                for="client_document"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Documento
                            </label>

                            <input
                                id="client_document"
                                name="client_document"
                                type="number"
                                value="{{ old('client_document') }}"
                                min="1"
                                required
                                class="mt-2 block w-full rounded-md
                                       border-gray-400 bg-white
                                       text-gray-900 text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            @error('client_document')
                                <p class="mt-2 text-red-700 text-[3vh]">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>


                    {{-- Servicio --}}
                    <div>

                        <label
                            for="service_id"
                            class="block font-semibold text-gray-900 text-[3vh]"
                        >
                            Servicio asociado
                        </label>

                        <select
                            id="service_id"
                            name="service_id"
                            required
                            class="mt-2 block w-full rounded-md
                                   border-gray-400 bg-white
                                   text-gray-900 text-[3vh]
                                   shadow-sm
                                   focus:border-indigo-600
                                   focus:ring-indigo-600"
                        >

                            <option value="">
                                Seleccioná un servicio
                            </option>

                            @foreach ($services as $service)

                                <option
                                    value="{{ $service->id }}"
                                    data-name="{{ $service->service }}"
                                    data-price="{{ $service->price }}"
                                    @selected(
                                        old('service_id') == $service->id
                                    )
                                >
                                    {{ $service->service }}
                                </option>

                            @endforeach

                        </select>

                        @error('service_id')
                            <p class="mt-2 text-red-700 text-[3vh]">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Nombre histórico del servicio --}}
                    <div>

                        <label
                            for="service_name"
                            class="block font-semibold text-gray-900 text-[3vh]"
                        >
                            Nombre del servicio
                        </label>

                        <input
                            id="service_name"
                            name="service_name"
                            type="text"
                            value="{{ old('service_name') }}"
                            required
                            readonly
                            class="mt-2 block w-full rounded-md
                                   border-gray-400 bg-gray-100
                                   text-gray-900 text-[3vh]
                                   shadow-sm"
                        >

                        <p class="mt-2 text-gray-700 text-[3vh]">
                            Se guarda el nombre del servicio tal como
                            estaba al momento de cargar la factura.
                        </p>

                        @error('service_name')
                            <p class="mt-2 text-red-700 text-[3vh]">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Datos económicos --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>

                            <label
                                for="price"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
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
                                class="mt-2 block w-full rounded-md
                                       border-gray-400 bg-white
                                       text-gray-900 text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            @error('price')
                                <p class="mt-2 text-red-700 text-[3vh]">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div>

                            <label
                                for="overdue_price"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
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
                                class="mt-2 block w-full rounded-md
                                       border-gray-400 bg-white
                                       text-gray-900 text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            @error('overdue_price')
                                <p class="mt-2 text-red-700 text-[3vh]">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>


                    {{-- Vencimiento --}}
                    <div>

                        <label
                            for="due_date"
                            class="block font-semibold text-gray-900 text-[3vh]"
                        >
                            Fecha de vencimiento
                        </label>

                        <input
                            id="due_date"
                            name="due_date"
                            type="date"
                            value="{{ old('due_date') }}"
                            required
                            class="mt-2 block w-full rounded-md
                                   border-gray-400 bg-white
                                   text-gray-900 text-[3vh]
                                   shadow-sm
                                   focus:border-indigo-600
                                   focus:ring-indigo-600"
                        >

                        @error('due_date')
                            <p class="mt-2 text-red-700 text-[3vh]">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Estado --}}
                    <div>

                        <label
                            for="payment_status"
                            class="block font-semibold text-gray-900 text-[3vh]"
                        >
                            Estado
                        </label>

                        <select
                            id="payment_status"
                            name="payment_status"
                            class="mt-2 block w-full rounded-md
                                   border-gray-400 bg-white
                                   text-gray-900 text-[3vh]
                                   shadow-sm
                                   focus:border-indigo-600
                                   focus:ring-indigo-600"
                        >

                            <option
                                value="pending"
                                @selected(
                                    old('payment_status', 'pending') === 'pending'
                                )
                            >
                                Pendiente
                            </option>

                            <option
                                value="paid"
                                @selected(
                                    old('payment_status') === 'paid'
                                )
                            >
                                Pagada
                            </option>

                        </select>

                        @error('payment_status')
                            <p class="mt-2 text-red-700 text-[3vh]">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Datos del pago --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <div>

                            <label
                                for="amount_paid"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Importe pagado
                            </label>

                            <input
                                id="amount_paid"
                                name="amount_paid"
                                type="number"
                                step="0.01"
                                min="0"
                                value="{{ old('amount_paid') }}"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400 bg-white
                                       text-gray-900 text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                        </div>


                        <div>

                            <label
                                for="paid_at"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Fecha de pago
                            </label>

                            <input
                                id="paid_at"
                                name="paid_at"
                                type="date"
                                value="{{ old('paid_at') }}"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400 bg-white
                                       text-gray-900 text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                        </div>


                        <div>

                            <label
                                for="payment_method"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Método de pago
                            </label>

                            <input
                                id="payment_method"
                                name="payment_method"
                                type="text"
                                value="{{ old('payment_method') }}"
                                placeholder="Ej. efectivo"
                                class="mt-2 block w-full rounded-md
                                       border-gray-400 bg-white
                                       text-gray-900 text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                        </div>

                    </div>


                    {{-- Botones --}}
                    <div class="pt-4 flex items-center justify-end gap-4">

                        <a
                            href="{{ route('admin.invoices.index') }}"
                            class="btn"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="btn"
                        >
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

        const serviceSelect = document.getElementById('service_id');
        const serviceName = document.getElementById('service_name');

        serviceSelect.addEventListener('change', function () {

            const selectedOption =
                serviceSelect.options[serviceSelect.selectedIndex];

            if (!selectedOption.value) {
                serviceName.value = '';
                return;
            }

            serviceName.value =
                selectedOption.dataset.name || '';

        });

        if (serviceSelect.value) {
            serviceSelect.dispatchEvent(new Event('change'));
        }

    });
</script>
```

</x-app-layout>
