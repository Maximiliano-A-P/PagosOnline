<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-white leading-tight text-[32px]">
            Registrar pago
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

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563; /* ring-gray-600 + offset */
        }
    </style>


    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

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
                        Factura #{{ $invoice->id }}
                    </h3>

                    <p>
                        Registrar el pago realizado por el cliente.
                    </p>

                </div>


                <div class="p-6">

                    {{-- Resumen --}}
                    <div class="bg-gray-100 border border-gray-300 rounded-lg p-6 mb-8">

                        <dl class="space-y-5">

                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Cliente
                                </dt>

                                <dd class="text-gray-900 text-[21px]">
                                    {{ $invoice->client_name }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Documento
                                </dt>

                                <dd class="text-gray-900 text-[21px]">
                                    {{ $invoice->client_document }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Servicio
                                </dt>

                                <dd class="text-gray-900 text-[21px]">
                                    {{ $invoice->service_name }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Precio original
                                </dt>

                                <dd class="text-gray-900 font-semibold text-[21px]">
                                    ${{ number_format($invoice->price, 2, ',', '.') }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Precio original con impuestos ({{ rtrim(rtrim(number_format((float) ($invoice->tax_percentage ?? 0), 2, '.', ''), '0'), '.') }}%)
                                </dt>

                                <dd class="text-gray-900 font-semibold text-[21px]">
                                    ${{ number_format($invoice->totalNormal(), 2, ',', '.') }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Precio vencido
                                </dt>

                                <dd class="text-gray-900 font-semibold text-[21px]">
                                    ${{ number_format($invoice->overdue_price, 2, ',', '.') }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Precio vencido con impuestos ({{ rtrim(rtrim(number_format((float) ($invoice->tax_percentage ?? 0), 2, '.', ''), '0'), '.') }}%)
                                </dt>

                                <dd class="text-gray-900 font-semibold text-[21px]">
                                    ${{ number_format($invoice->totalVencido(), 2, ',', '.') }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Vencimiento
                                </dt>

                                <dd class="text-gray-900 text-[21px]">
                                    {{ $invoice->due_date->format('d/m/Y') }}
                                </dd>

                            </div>

                        </dl>

                    </div>


                    {{-- Formulario --}}
                    <form
                        method="POST"
                        action="{{ route('admin.invoices.pay', $invoice) }}"
                        class="space-y-7"
                    >

                        @csrf


                        {{-- Importe (calculado según la fecha de pago) --}}
                        <div>

                            <span class="block font-semibold text-gray-900 text-[21px]">
                                Importe a registrar
                            </span>

                            <p
                                id="amountPreview"
                                class="mt-2 text-gray-900 font-semibold text-[21px]"
                            ></p>

                            <p
                                id="amountHint"
                                class="mt-1 text-gray-600 text-[21px]"
                            ></p>

                        </div>


                        {{-- Método de pago --}}
                        <div>

                            <label
                                for="payment_method"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                Método de pago
                            </label>

                            <select
                                id="payment_method"
                                name="payment_method"
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

                                <option value="">
                                    Seleccionar
                                </option>

                                <option
                                    value="cash"
                                    @selected(old('payment_method') === 'cash')
                                >
                                    Efectivo
                                </option>

                                <option
                                    value="transfer"
                                    @selected(old('payment_method') === 'transfer')
                                >
                                    Transferencia
                                </option>

                                <option
                                    value="card"
                                    @selected(old('payment_method') === 'card')
                                >
                                    Tarjeta
                                </option>

                                <option
                                    value="other"
                                    @selected(old('payment_method') === 'other')
                                >
                                    Otro
                                </option>

                            </select>

                            @error('payment_method')

                                <p class="mt-2 text-red-700 text-[21px]">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Fecha --}}
                        <div>

                            <label
                                for="paid_at"
                                class="block font-semibold text-gray-900 text-[21px]"
                            >
                                Fecha del pago
                            </label>

                            <input
                                id="paid_at"
                                name="paid_at"
                                type="date"
                                value="{{ old('paid_at', now()->toDateString()) }}"
                                min="{{ $invoice->issued_at->toDateString() }}"
                                max="{{ now()->toDateString() }}"
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

                            @error('paid_at')

                                <p class="mt-2 text-red-700 text-[21px]">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Botones --}}
                        <div class="pt-4 flex items-center justify-end gap-4">

                            <a
                                href="{{ route('admin.invoices.show', $invoice) }}"
                                class="btn"
                            >
                                Cancelar
                            </a>


                            <button
                                type="submit"
                                class="btn"
                            >
                                Registrar pago
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dueDate    = @json($invoice->due_date->toDateString());
            const totalNormal  = @json($invoice->totalNormal());
            const totalVencido = @json($invoice->totalVencido());

            const paidAt  = document.getElementById('paid_at');
            const preview = document.getElementById('amountPreview');
            const hint    = document.getElementById('amountHint');

            const money = (n) => '$' + n.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            function update() {
                if (!paidAt.value) {
                    preview.textContent = '—';
                    hint.textContent = 'Elegí la fecha del pago.';
                    return;
                }

                // YYYY-MM-DD se compara bien como texto.
                // Rige el precio vencido desde el día siguiente al vencimiento.
                const vencida = paidAt.value > dueDate;

                preview.textContent = money(vencida ? totalVencido : totalNormal);
                hint.textContent = vencida
                    ? 'Pagada después del vencimiento: precio vencido (con impuestos).'
                    : 'Pagada en término: precio normal (con impuestos).';
            }

            paidAt.addEventListener('input', update);
            paidAt.addEventListener('change', update);
            update();
        });
    </script>
</x-app-layout>