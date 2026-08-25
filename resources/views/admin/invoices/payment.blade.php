<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-white leading-tight text-[4vh]">
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
            font-size: 20px;
            margin: 0;
        }

        .card-header p {
            color: #d1d5db; /* gray-300 */
            font-size: 14px;
            margin: 8px 0 0;
        }

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
    </style>


    <div class="py-12">

        <div class="max-w-3xl mx-auto px-6 lg:px-8">

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

                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Cliente
                                </dt>

                                <dd class="text-gray-900 text-[3vh]">
                                    {{ $invoice->client_name }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Documento
                                </dt>

                                <dd class="text-gray-900 text-[3vh]">
                                    {{ $invoice->client_document }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Servicio
                                </dt>

                                <dd class="text-gray-900 text-[3vh]">
                                    {{ $invoice->service_name }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Precio original
                                </dt>

                                <dd class="text-gray-900 font-semibold text-[3vh]">
                                    ${{ number_format($invoice->price, 2, ',', '.') }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Precio vencido
                                </dt>

                                <dd class="text-gray-900 font-semibold text-[3vh]">
                                    ${{ number_format($invoice->overdue_price, 2, ',', '.') }}
                                </dd>

                            </div>


                            <div class="flex flex-col md:flex-row md:justify-between gap-2">

                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Vencimiento
                                </dt>

                                <dd class="text-gray-900 text-[3vh]">
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


                        {{-- Importe --}}
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
                                value="{{ old('amount_paid', $invoice->price) }}"
                                required
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            @error('amount_paid')

                                <p class="mt-2 text-red-700 text-[3vh]">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Método de pago --}}
                        <div>

                            <label
                                for="payment_method"
                                class="block font-semibold text-gray-900 text-[3vh]"
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
                                       text-[3vh]
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

                                <p class="mt-2 text-red-700 text-[3vh]">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        {{-- Fecha y hora --}}
                        <div>

                            <label
                                for="paid_at"
                                class="block font-semibold text-gray-900 text-[3vh]"
                            >
                                Fecha y hora del pago
                            </label>

                            <input
                                id="paid_at"
                                name="paid_at"
                                type="datetime-local"
                                value="{{ old(
                                    'paid_at',
                                    now()->format('Y-m-d\TH:i')
                                ) }}"
                                required
                                class="mt-2 block w-full rounded-md
                                       border-gray-400
                                       bg-white
                                       text-gray-900
                                       text-[3vh]
                                       shadow-sm
                                       focus:border-indigo-600
                                       focus:ring-indigo-600"
                            >

                            @error('paid_at')

                                <p class="mt-2 text-red-700 text-[3vh]">
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

</x-app-layout>