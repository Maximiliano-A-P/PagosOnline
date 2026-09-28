<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <h2 class="font-semibold text-white leading-tight text-[32px]">
                Detalle de factura
            </h2>

            <a
                href="{{ route('admin.invoices.index') }}"
                class="btn"
            >
                Volver
            </a>

        </div>

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
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563; /* ring-gray-600 + offset */
        }

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

        .section-header {
            background-color: #111827; /* gray-900 */
            color: #ffffff;
            font-weight: 600;
            font-size: 24px;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 18px;
            color: #ffffff;
        }

        .badge-paid {
            background-color: #15803d; /* green-700 */
        }

        .badge-pending {
            background-color: #111827; /* gray-900 */
        }
    </style>


    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            {{-- Mensaje de éxito --}}
            @if (session('success'))

                <div
                    class="mb-8 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- Mensaje de error --}}
            @if (session('error'))

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('error') }}
                </div>

            @endif


            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="card-header">

                    <h3>
                        Información de la factura
                    </h3>

                </div>


                <div class="p-6">

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                ID
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                #{{ $invoice->id }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Fecha de emisión
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->issued_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Cliente
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->client_name }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Documento
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->client_document }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Servicio
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->service_name }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                ID del servicio
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->service_id ?? 'Sin servicio asociado' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Precio
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                ${{ number_format($invoice->price, 2, ',', '.') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Precio vencido
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                ${{ number_format($invoice->overdue_price, 2, ',', '.') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Vencimiento
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->due_date->format('d/m/Y') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Estado
                            </dt>

                            <dd class="mt-2">

                                @if ($invoice->payment_status === 'paid')

                                    <span class="badge badge-paid">
                                        Pagada
                                    </span>

                                @else

                                    <span class="badge badge-pending">
                                        Pendiente
                                    </span>

                                @endif

                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Importe pagado
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                @if ($invoice->amount_paid !== null)
                                    ${{ number_format($invoice->amount_paid, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Fecha de pago
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->paid_at?->format('d/m/Y H:i') ?? '—' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Método de pago
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->payment_method ?? '—' }}
                            </dd>
                        </div>

                    </dl>


                    {{-- Datos ARCA --}}
                    <div class="mt-12 pt-8 border-t border-gray-300">

                        <h3 class="section-header">
                            Datos ARCA
                        </h3>

                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">

                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Estado ARCA
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ $invoice->arca_status ?? 'Sin procesar' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    CAE
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ $invoice->arca_cae ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Tipo de comprobante
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ $invoice->arca_invoice_type ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Punto de venta
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ $invoice->arca_point_of_sale ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Número de comprobante
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ $invoice->arca_invoice_number ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Vencimiento CAE
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ $invoice->arca_cae_expires_at?->format('d/m/Y') ?? '—' }}
                                </dd>
                            </div>

                        </dl>

                    </div>


                    {{-- Acciones --}}
                    <div class="mt-12 flex items-center gap-4">

                        @if ($invoice->payment_status !== 'paid')

                            <a
                                href="{{ route('admin.invoices.payment', $invoice) }}"
                                class="btn"
                            >
                                Registrar pago
                            </a>

                        @endif

                        @if (
                            $invoice->payment_status === 'paid'
                            && empty($invoice->arca_cae)
                            && $invoice->arca_status !== 'aprobado'
                        )
                            <form
                                method="POST"
                                action="{{ route('admin.invoices.retry-arca', $invoice) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn"
                                >
                                    Reintentar ARCA
                                </button>
                            </form>
                        @endif

                        @if (
                            $invoice->payment_status !== 'paid'
                            && $invoice->amount_paid === null
                            && empty($invoice->mercadopago_payment_id)
                            && empty($invoice->arca_cae)
                            && $invoice->arca_status !== 'aprobado'
                        )
                            <form
                                method="POST"
                                action="{{ route('admin.invoices.destroy', $invoice) }}"
                                onsubmit="return confirm(
                                    '¿Eliminar esta factura? Esta acción no se puede deshacer.'
                                );"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn"
                                >
                                    Eliminar
                                </button>
                            </form>
                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>