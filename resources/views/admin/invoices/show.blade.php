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

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #4b5563;
        }

        .btn-danger {
            background-color: #991b1b;
            border-color: #991b1b;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

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

        .section-header {
            background-color: #111827;
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

        .badge-paid,
        .badge-approved {
            background-color: #15803d;
        }

        .badge-pending {
            background-color: #111827;
        }

        .badge-error {
            background-color: #b91c1c;
        }

        .retention-notice {
            border: 1px solid #d97706;
            background-color: #fffbeb;
            color: #92400e;
            padding: 16px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 21px;
        }

        .export-notice {
            border: 1px solid #93c5fd;
            background-color: #eff6ff;
            color: #1e40af;
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 21px;
        }
    </style>

    @php
        $taxPercentage = (float) ($invoice->tax_percentage ?? 0);

        $priceWithTax = round(
            (float) $invoice->price * (1 + ($taxPercentage / 100)),
            2
        );

        $overduePriceWithTax = round(
            (float) $invoice->overdue_price * (1 + ($taxPercentage / 100)),
            2
        );

        $tipoComprobante = (int) ($invoice->arca_invoice_type ?? 0);

        $tiposComprobante = [
            1 => 'Factura A',
            2 => 'Nota de débito A',
            3 => 'Nota de crédito A',
            4 => 'Recibo A',
            5 => 'Nota de venta al contado A',

            6 => 'Factura B',
            7 => 'Nota de débito B',
            8 => 'Nota de crédito B',
            9 => 'Recibo B',
            10 => 'Nota de venta al contado B',

            11 => 'Factura C',
            12 => 'Nota de débito C',
            13 => 'Nota de crédito C',
            15 => 'Recibo C',

            19 => 'Factura E',
            20 => 'Nota de débito E',
            21 => 'Nota de crédito E',

            51 => 'Factura A con leyenda de retención',
            52 => 'Nota de débito A con leyenda de retención',
            53 => 'Nota de crédito A con leyenda de retención',
        ];

        $letrasComprobante = [
            1 => 'A',
            2 => 'A',
            3 => 'A',
            4 => 'A',
            5 => 'A',

            6 => 'B',
            7 => 'B',
            8 => 'B',
            9 => 'B',
            10 => 'B',

            11 => 'C',
            12 => 'C',
            13 => 'C',
            15 => 'C',

            19 => 'E',
            20 => 'E',
            21 => 'E',

            51 => 'A',
            52 => 'A',
            53 => 'A',
        ];

        $esExportacion = in_array(
            $tipoComprobante,
            [19, 20, 21],
            true
        ) || (int) ($invoice->client_iva_condition ?? 0) === 9;

        $esAConLeyendaRetencion = in_array(
            $tipoComprobante,
            [51, 52, 53],
            true
        );

        $nombreComprobante = $tiposComprobante[$tipoComprobante]
            ?? ($esExportacion ? 'Factura E' : null);

        $letraComprobante = $letrasComprobante[$tipoComprobante]
            ?? ($esExportacion ? 'E' : null);

        $identificadorFiscalExtranjero =
            $invoice->client_arca_cuit_pais_cliente
            ?: $invoice->client_arca_id_impositivo;

        $condicionesIva = [
            1 => 'IVA Responsable Inscripto',
            4 => 'IVA Sujeto Exento',
            5 => 'Consumidor Final',
            6 => 'Responsable Monotributo',
            7 => 'Sujeto No Categorizado',
            9 => 'Cliente del exterior',
            13 => 'Monotributista Social',
            15 => 'IVA No Alcanzado',
            16 => 'Monotributo Trabajador Independiente Promovido',
        ];

        $condicionIva = $condicionesIva[
            (int) ($invoice->client_iva_condition ?? 0)
        ] ?? 'No especificada';

        $estadoArca = $invoice->arca_status ?: 'Sin procesar';

        $estadoArcaNormalizado = strtolower(
            (string) $invoice->arca_status
        );

        $arcaAprobado = $estadoArcaNormalizado === 'aprobado';

        $arcaError = str_starts_with(
            $estadoArcaNormalizado,
            'error'
        ) || in_array(
            $estadoArcaNormalizado,
            ['rechazado', 'fallido'],
            true
        );

        $numeroComprobante = null;

        if (
            $invoice->arca_point_of_sale !== null
            && $invoice->arca_invoice_number !== null
        ) {
            $numeroComprobante = str_pad(
                (string) $invoice->arca_point_of_sale,
                5,
                '0',
                STR_PAD_LEFT
            ) . '-' . str_pad(
                (string) $invoice->arca_invoice_number,
                8,
                '0',
                STR_PAD_LEFT
            );
        }
    @endphp

    <div class="py-12">

        <div class="mx-auto" style="width: 90vw;">

            @if (session('success'))
                <div
                    class="mb-8 rounded-lg bg-green-700 border border-green-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    class="mb-8 rounded-lg bg-red-700 border border-red-800
                           text-white px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if (session('arca_warning'))
                <div
                    class="mb-8 rounded-lg bg-yellow-100 border border-yellow-400
                           text-yellow-900 px-6 py-4 shadow-sm text-[21px]"
                >
                    {{ session('arca_warning') }}
                </div>
            @endif

            <div class="bg-white border border-gray-300 rounded-lg shadow-sm overflow-hidden">

                <div class="card-header">
                    <h3>
                        Información de la factura
                    </h3>
                </div>

                <div class="p-6">

                    @if ($esAConLeyendaRetencion)
                        <div class="retention-notice mb-6">
                            OPERACIÓN SUJETA A RETENCIÓN
                        </div>
                    @endif

                    @if ($esExportacion)
                        <div class="export-notice mb-6">
                            <strong>Comprobante de exportación.</strong>
                            Corresponde a un cliente del exterior y se procesa
                            mediante el servicio WSFEX de ARCA.
                        </div>
                    @endif

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
                                {{ $invoice->issued_at?->format('d/m/Y H:i') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Vencimiento
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Cliente
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->client_name ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                {{ $esExportacion ? 'Identificador fiscal extranjero' : 'Documento' }}
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                @if ($esExportacion)
                                    {{ $identificadorFiscalExtranjero ?: '—' }}
                                @else
                                    {{ $invoice->client_document ?? '—' }}
                                @endif
                            </dd>
                        </div>

                        @unless ($esExportacion)
                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    CUIT del cliente
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ $invoice->client_cuit ?: '—' }}
                                </dd>
                            </div>
                        @endunless

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Condición frente al IVA
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $condicionIva }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Servicio
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                {{ $invoice->service_name ?? '—' }}
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
                                ${{ number_format((float) $invoice->price, 2, ',', '.') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-semibold text-gray-700 text-[21px]">
                                Precio vencido
                            </dt>

                            <dd class="mt-2 text-gray-900 text-[21px]">
                                ${{ number_format((float) $invoice->overdue_price, 2, ',', '.') }}
                            </dd>
                        </div>

                        @unless ($esExportacion)
                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Impuestos %
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    {{ number_format($taxPercentage, 2, ',', '.') }}%
                                </dd>
                            </div>

                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Neto + Impuestos
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    ${{ number_format($priceWithTax, 2, ',', '.') }}
                                </dd>
                            </div>

                            <div>
                                <dt class="font-semibold text-gray-700 text-[21px]">
                                    Vencido + Impuestos
                                </dt>

                                <dd class="mt-2 text-gray-900 text-[21px]">
                                    ${{ number_format($overduePriceWithTax, 2, ',', '.') }}
                                </dd>
                            </div>
                        @endunless

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
                                    ${{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}
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
                                @if ($invoice->payment_method === 'mercadopago')
                                    Mercado Pago
                                @else
                                    {{ $invoice->payment_method ?? '—' }}
                                @endif
                            </dd>
                        </div>

                    </dl>

                    @if ($esExportacion)

                        <div class="mt-12 pt-8 border-t border-gray-300">

                            <h3 class="section-header">
                                Datos de exportación
                            </h3>

                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Domicilio del cliente extranjero
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->client_address ?: '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Código de país de destino ARCA
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->client_arca_destination_country ?: '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        CUIT del país del cliente
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->client_arca_cuit_pais_cliente ?: '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Identificación impositiva del cliente
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->client_arca_id_impositivo ?: '—' }}
                                    </dd>
                                </div>

                            </dl>

                        </div>

                    @endif

                    {{-- Los datos ARCA solo se muestran después de registrar el pago. --}}
                    @if ($invoice->payment_status === 'paid')

                        <div class="mt-12 pt-8 border-t border-gray-300">

                            <h3 class="section-header">
                                Datos ARCA — {{ $esExportacion ? 'WSFEX' : 'WSFE' }}
                            </h3>

                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Estado ARCA
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        @if ($arcaAprobado)
                                            <span class="badge badge-approved">
                                                {{ $estadoArca }}
                                            </span>
                                        @elseif ($arcaError)
                                            <span class="badge badge-error">
                                                {{ $estadoArca }}
                                            </span>
                                        @else
                                            <span class="badge badge-pending">
                                                {{ $estadoArca }}
                                            </span>
                                        @endif
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Tipo de comprobante
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $nombreComprobante ?? 'Sin determinar' }}

                                        @if ($tipoComprobante > 0)
                                            (tipo {{ $tipoComprobante }})
                                        @endif

                                        @if ($letraComprobante)
                                            — Letra {{ $letraComprobante }}
                                        @endif
                                    </dd>
                                </div>

                                @if ($esAConLeyendaRetencion)
                                    <div>
                                        <dt class="font-semibold text-gray-700 text-[21px]">
                                            Leyenda del comprobante
                                        </dt>

                                        <dd class="mt-2 text-gray-900 text-[21px] font-bold">
                                            OPERACIÓN SUJETA A RETENCIÓN
                                        </dd>
                                    </div>
                                @endif

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
                                        {{ $numeroComprobante ?? '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        CAE
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->arca_cae ?: '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Vencimiento del CAE
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->arca_cae_expires_at?->format('d/m/Y') ?? '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Último intento de emisión
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->arca_last_attempt_at?->format('d/m/Y H:i') ?? '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-semibold text-gray-700 text-[21px]">
                                        Cantidad de reintentos
                                    </dt>

                                    <dd class="mt-2 text-gray-900 text-[21px]">
                                        {{ $invoice->arca_retry_attempts ?? 0 }}
                                    </dd>
                                </div>

                            </dl>

                        </div>

                    @endif

                    {{-- Acciones --}}
                    <div class="mt-12 pt-8 border-t border-gray-300">

                        <div class="flex items-center gap-4 flex-wrap">

                            <a
                                href="{{ route('admin.invoices.pdf', $invoice) }}"
                                class="btn"
                            >
                                Descargar PDF
                            </a>

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
                                && ! $arcaAprobado
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
                                        Reintentar {{ $esExportacion ? 'WSFEX' : 'ARCA' }}
                                    </button>
                                </form>
                            @endif

                            @if (
                                $invoice->payment_status !== 'paid'
                                && $invoice->amount_paid === null
                                && empty($invoice->mercadopago_payment_id)
                                && empty($invoice->arca_cae)
                                && ! $arcaAprobado
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
                                        class="btn btn-danger"
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

    </div>

</x-app-layout>