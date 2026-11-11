<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $pagada && $conCae ? $comprobanteNombre : 'Detalle de factura' }}</title>

    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            vertical-align: top;
        }

        .box {
            border: 1px solid #9ca3af;
            padding: 10px 12px;
        }

        .header {
            margin-bottom: 14px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 6px;
        }

        .muted {
            color: #6b7280;
        }

        .label {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .value {
            font-size: 11px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #d1d5db;
        }

        .section {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .field {
            padding: 5px 8px 5px 0;
        }

        .letter {
            border: 2px solid #111827;
            width: 46px;
            height: 46px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            line-height: 46px;
            margin: 0 auto;
        }

        .small {
            font-size: 9px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .items th {
            background: #111827;
            color: #ffffff;
            text-align: left;
            padding: 7px 8px;
            font-size: 10px;
        }

        .items td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals td {
            padding: 5px 8px;
        }

        .totals .grand td {
            border-top: 2px solid #111827;
            font-size: 14px;
            font-weight: bold;
            padding-top: 8px;
        }

        .stamp {
            display: inline-block;
            border: 3px solid #15803d;
            color: #15803d;
            font-size: 18px;
            font-weight: bold;
            padding: 5px 16px;
        }

        .stamp-pending {
            border-color: #b45309;
            color: #b45309;
        }

        .retention-legend {
            margin: 8px 0;
            border: 1px solid #111827;
            padding: 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }

        .export-note {
            margin-top: 8px;
            padding: 8px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
        }

        .cae-section {
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .qr {
            width: 130px;
            height: 130px;
        }
    </style>
</head>

<body>

@php
    /*
     * Período facturado a partir de las fechas históricas guardadas
     * en la factura. Se cuenta cada mes del período, incluidos
     * el mes inicial y el final.
     */
    $periodoMeses = null;

    if ($invoice->service_period_start && $invoice->service_period_end) {
        $periodoMeses = $invoice->service_period_start
            ->copy()
            ->startOfMonth()
            ->diffInMonths(
                $invoice->service_period_end->copy()->startOfMonth()
            ) + 1;
    } elseif ($invoice->service_id && $invoice->service) {
        $periodoMeses = $invoice->service->period;
    }

    $precioNeto = (float) $invoice->price;
    $precioVencidoNeto = (float) $invoice->overdue_price;
    $porcentajeImpuestos = (float) ($invoice->tax_percentage ?? 0);

    $precioConImpuestos = round(
        $precioNeto * (1 + $porcentajeImpuestos / 100),
        2
    );

    $precioVencidoConImpuestos = round(
        $precioVencidoNeto * (1 + $porcentajeImpuestos / 100),
        2
    );

    $diaVencimiento = $invoice->due_date?->format('d');
@endphp

{{-- Encabezado e ícono del comprobante --}}
<table class="header">
    <tr>
        <td>
            <p class="title">
                {{ $pagada && $conCae ? $comprobanteNombre : 'DETALLE DE FACTURA' }}
            </p>

            <p class="muted small">
                Documento correspondiente al servicio contratado.
            </p>
        </td>

        @if ($pagada && $conCae && $letra)
            <td style="width: 76px; padding-left: 10px;">
                <div class="letter">{{ $letra }}</div>

                <div class="small muted center" style="margin-top: 4px;">
                    Código {{ str_pad((string) $tipoComprobante, 2, '0', STR_PAD_LEFT) }}
                </div>
            </td>
        @endif
    </tr>
</table>

{{-- 1. Datos del cliente --}}
<div class="box section">
    <p class="section-title">1. Datos del cliente</p>

    <table>
        <tr>
            <td class="field" style="width: 40%;">
                <div class="label">Nombre o razón social</div>
                <div class="value">{{ $clientName ?: '—' }}</div>
            </td>

            <td class="field" style="width: 30%;">
                <div class="label">{{ $clientDocumentLabel }}</div>
                <div class="value">{{ $clientDocument ?: '—' }}</div>
            </td>

            <td class="field" style="width: 30%;">
                @if ($esExportacion)
                    <div class="label">País de destino (código ARCA)</div>
                    <div class="value">
                        {{ $clientDestinationCountry ?: '—' }}
                    </div>
                @else
                    <div class="label">CUIT</div>
                    <div class="value">{{ $clientCuit ?: '—' }}</div>
                @endif
            </td>
        </tr>

        @if ($esExportacion)
            <tr>
                <td colspan="3" class="field">
                    <div class="label">Domicilio del comprador</div>
                    <div class="value">{{ $clientAddress ?: '—' }}</div>
                </td>
            </tr>

            @if ($clientForeignTaxId || $clientIdImpositivo)
                <tr>
                    <td colspan="3" class="field">
                        <div class="label">Identificación fiscal extranjera</div>
                        <div class="value">
                            {{ $clientForeignTaxId ?: $clientIdImpositivo }}
                        </div>
                    </td>
                </tr>
            @endif
        @else
            <tr>
                <td colspan="3" class="field">
                    <div class="label">Condición frente al IVA</div>
                    <div class="value">
                        {{ $codigoIva }} — {{ $condicionIva }}
                    </div>
                </td>
            </tr>

            <tr>
                <td class="field" style="width: 40%;">
                    <div class="label">Teléfono</div>
                    <div class="value">{{ $client?->phone ?: '—' }}</div>
                </td>

                <td colspan="2" class="field">
                    <div class="label">Email</div>
                    <div class="value">{{ $client?->email ?: '—' }}</div>
                </td>
            </tr>

            <tr>
                <td colspan="3" class="field">
                    <div class="label">Dirección</div>
                    <div class="value">{{ $clientAddress ?: '—' }}</div>
                </td>
            </tr>
        @endif
    </table>
</div>

{{-- 2. Datos del servicio --}}
<div class="box section">
    <p class="section-title">2. Datos del servicio</p>

    <table>
        <tr>
            <td colspan="2" class="field">
                <div class="label">Descripción del servicio</div>
                <div class="value">{{ $invoice->service_name }}</div>
            </td>
        </tr>

        <tr>
            <td class="field" style="width: 50%;">
                <div class="label">Período facturado</div>
                <div class="value">
                    @if ($periodoMeses !== null)
                        {{ $periodoMeses }} {{ $periodoMeses == 1 ? 'mes' : 'meses' }}
                    @else
                        No especificado
                    @endif
                </div>
            </td>

            <td class="field" style="width: 50%;">
                <div class="label">Día de vencimiento</div>
                <div class="value">
                    {{ $diaVencimiento ? (int) $diaVencimiento : '—' }}
                </div>
            </td>
        </tr>

        <tr>
            <td class="field" style="width: 50%;">
                <div class="label">Período desde</div>
                <div class="value">
                    {{ $invoice->service_period_start?->format('d/m/Y') ?: '—' }}
                </div>
            </td>

            <td class="field" style="width: 50%;">
                <div class="label">Período hasta</div>
                <div class="value">
                    {{ $invoice->service_period_end?->format('d/m/Y') ?: '—' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 10px;">
        <thead>
            <tr>
                <th>Detalle del precio</th>
                <th class="right">Importe</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>Precio neto</td>
                <td class="right">
                    ${{ number_format($precioNeto, 2, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td>Precio vencido neto</td>
                <td class="right">
                    ${{ number_format($precioVencidoNeto, 2, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td>Impuestos</td>
                <td class="right">
                    {{ rtrim(rtrim(number_format($porcentajeImpuestos, 2, '.', ''), '0'), '.') }}%
                </td>
            </tr>

            <tr>
                <td>Neto + impuestos</td>
                <td class="right">
                    ${{ number_format($precioConImpuestos, 2, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td>Vencido + impuestos</td>
                <td class="right">
                    ${{ number_format($precioVencidoConImpuestos, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

{{-- 3. Datos de la factura --}}
<div class="box section">
    <p class="section-title">3. Datos de la factura</p>

    <table>
        <tr>
            <td class="field" style="width: 33%;">
                <div class="label">Fecha de emisión del registro</div>
                <div class="value">
                    {{ $invoice->issued_at?->format('d/m/Y') ?: '—' }}
                </div>
            </td>

            <td class="field" style="width: 33%;">
                <div class="label">Vencimiento</div>
                <div class="value">
                    {{ $invoice->due_date?->format('d/m/Y') ?: '—' }}
                </div>
            </td>

            <td class="field" style="width: 34%;">
                <div class="label">Estado de pago</div>
                <div class="value">
                    {{ $pagada ? 'Pagada' : 'Pendiente de pago' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="center" style="margin: 12px 0;">
        @if ($pagada)
            <span class="stamp">PAGADA</span>
        @else
            <span class="stamp stamp-pending">PENDIENTE DE PAGO</span>
        @endif
    </div>

    {{-- Importes y recibo: aparecen solamente cuando está pagada --}}
    @if ($pagada)
        <table class="items">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="right" style="width: 35%;">Importe</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Subtotal</td>
                    <td class="right">
                        ${{ number_format($neto, 2, ',', '.') }}
                    </td>
                </tr>

                @unless ($esExportacion)
                    <tr>
                        <td>
                            IVA
                            {{ rtrim(rtrim(number_format($tasa, 2, '.', ''), '0'), '.') }}%
                        </td>
                        <td class="right">
                            ${{ number_format($iva, 2, ',', '.') }}
                        </td>
                    </tr>
                @endunless

                <tr>
                    <td>Moneda</td>
                    <td class="right">{{ $monedaCodigo }}</td>
                </tr>

                <tr>
                    <td><strong>Total</strong></td>
                    <td class="right">
                        <strong>
                            ${{ number_format($total, 2, ',', '.') }}
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 14px;">
            <p class="section-title">Recibo de pago</p>

            <table>
                <tr>
                    <td class="field" style="width: 25%;">
                        <div class="label">Fecha de pago</div>
                        <div class="value">
                            {{ $invoice->paid_at?->format('d/m/Y') ?: '—' }}
                        </div>
                    </td>

                    <td class="field" style="width: 25%;">
                        <div class="label">Medio de pago</div>
                        <div class="value">{{ $metodoPago ?: '—' }}</div>
                    </td>

                    <td class="field" style="width: 25%;">
                        <div class="label">Importe abonado</div>
                        <div class="value">
                            ${{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}
                        </div>
                    </td>

                    <td class="field" style="width: 25%;">
                        <div class="label">N.º de operación</div>
                        <div class="value">
                            {{ $invoice->mercadopago_payment_id ?: '—' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif
</div>

{{-- 4. Datos de ARCA: no existe este apartado en facturas impagas --}}
@if ($pagada)
    <div class="box section">
        <p class="section-title">4. Datos de ARCA</p>

        @if ($conCae)
            <table>
                <tr>
                    <td class="field" style="width: 50%;">
                        <div class="label">Tipo de comprobante</div>
                        <div class="value">{{ $comprobanteNombre }}</div>
                    </td>

                    <td class="field" style="width: 50%;">
                        <div class="label">Número de comprobante</div>
                        <div class="value">{{ $numero ?: '—' }}</div>
                    </td>
                </tr>

                <tr>
                    <td class="field">
                        <div class="label">Fecha del comprobante ARCA</div>
                        <div class="value">
                            {{ $fechaComprobante?->format('d/m/Y') ?: '—' }}
                        </div>
                    </td>

                    <td class="field">
                        <div class="label">Punto de venta</div>
                        <div class="value">
                            {{ $invoice->arca_point_of_sale ?: '—' }}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="field">
                        <div class="label">Estado de emisión</div>
                        <div class="value">Aprobado por ARCA</div>
                    </td>
                </tr>

                <tr>
                    <td class="field">
                        <div class="label">CAE</div>
                        <div class="value">{{ $invoice->arca_cae }}</div>
                    </td>

                    <td class="field">
                        <div class="label">Vencimiento del CAE</div>
                        <div class="value">
                            {{ $invoice->arca_cae_expires_at?->format('d/m/Y') ?: '—' }}
                        </div>
                    </td>
                </tr>
            </table>

            @if ($leyendaComprobante)
                <div class="retention-legend">
                    {{ $leyendaComprobante }}
                </div>
            @endif

            @if ($qr)
                <div class="center cae-section">
                    <p class="section-title">Código QR del comprobante</p>

                    <img
                        class="qr"
                        src="{{ $qr }}"
                        alt="Código QR del comprobante ARCA"
                    >

                    <p class="small muted">
                        Escaneá el código QR para consultar los datos del comprobante.
                    </p>
                </div>
            @endif
        @else
            <p class="value">
                {{ $invoice->arca_status ?: 'Pendiente de emisión fiscal.' }}
            </p>

            @if ($fechaComprobante)
                <p class="small muted" style="margin-top: 8px;">
                    Último intento ARCA:
                    {{ $fechaComprobante->format('d/m/Y') }}
                </p>
            @endif
        @endif
    </div>
@endif

</body>
</html>