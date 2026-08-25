<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <h2 class="font-semibold text-white leading-tight text-[4vh]">
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

        .section-header {
            background-color: #111827; /* gray-900 */
            color: #ffffff;
            font-weight: 600;
            font-size: 20px;
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
            font-size: 13px;
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

        <div class="max-w-5xl mx-auto px-6 lg:px-8">

            {{-- Mensaje de éxito --}}
            @if (session('success'))

                <div
                    class="mb-8 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[3vh]"
                >
                    {{ session('success') }}
                </div>

            @endif


            {{-- Mensaje de error --}}
            @if (session('error'))

                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm text-[3vh]"
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
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                ID
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                #{{ $invoice->id }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Fecha de emisión
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                {{ $invoice->issued_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Cliente
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                {{ $invoice->client_name }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Documento
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                {{ $invoice->client_document }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Servicio
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                {{ $invoice->service_name }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                ID del servicio
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                {{ $invoice->service_id ?? 'Sin servicio asociado' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Precio
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                ${{ number_format($invoice->price, 2, ',', '.') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Precio vencido
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                ${{ number_format($invoice->overdue_price, 2, ',', '.') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Vencimiento
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                {{ $invoice->due_date->format('d/m/Y') }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
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
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Importe pagado
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                @if ($invoice->amount_paid !== null)
                                    ${{ number_format($invoice->amount_paid, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Fecha de pago
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
                                {{ $invoice->paid_at?->format('d/m/Y H:i') ?? '—' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="font-semibold text-gray-700 text-[3vh]">
                                Método de pago
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[3vh]">
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
                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Estado ARCA
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[3vh]">
                                    {{ $invoice->arca_status ?? 'Sin procesar' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    CAE
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[3vh]">
                                    {{ $invoice->arca_cae ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Tipo de comprobante
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[3vh]">
                                    {{ $invoice->arca_invoice_type ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Punto de venta
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[3vh]">
                                    {{ $invoice->arca_point_of_sale ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Número de comprobante
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[3vh]">
                                    {{ $invoice->arca_invoice_number ?? '—' }}
                                </dd>
                            </div>


                            <div>
                                <dt class="font-semibold text-gray-700 text-[3vh]">
                                    Vencimiento CAE
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[3vh]">
                                    {{ $invoice->arca_cae_expires_at?->format('d/m/Y H:i') ?? '—' }}
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

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>