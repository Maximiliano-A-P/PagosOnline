<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $comprobanteNombre }}</title>

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

        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 8px;
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
            color: #fff;
            text-align: left;
            padding: 7px 8px;
            font-size: 10px;
        }

        .items td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals td {
            padding: 4px 8px;
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
            font-size: 20px;
            font-weight: bold;
            padding: 5px 16px;
        }

        .stamp-pending {
            border-color: #b45309;
            color: #b45309;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 8px;
        }

        .retention-legend {
            margin-top: 10px;
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
            margin-top: 18px;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    {{-- Encabezado --}}
    <table>
        <tr>
            <td>
                <div class="box">
                    <p class="title">
                        {{ $conCae ? $comprobanteNombre : 'DETALLE DE FACTURA' }}
                    </p>

                    @if ($numero)
                        <div style="margin-bottom: 10px;">
                            <span class="label">Número de comprobante</span>
                            <div class="value">{{ $numero }}</div>
                        </div>
                    @endif

                    <table>
                        <tr>
                            <td style="width: 33%; padding-right: 8px;">
                                <div class="label">Fecha histórica de emisión</div>
                                <div class="value">
                                    {{ $invoice->issued_at?->format('d/m/Y') ?: '—' }}
                                </div>
                            </td>

                            <td style="width: 33%; padding-right: 8px;">
                                <div class="label">
                                    {{ $conCae ? 'Fecha del comprobante ARCA' : 'Último intento ARCA' }}
                                </div>
                                <div class="value">
                                    {{ $fechaComprobante?->format('d/m/Y') ?: '—' }}
                                </div>
                            </td>

                            <td style="width: 34%;">
                                <div class="label">Vencimiento</div>
                                <div class="value">
                                    {{ $invoice->due_date?->format('d/m/Y') ?: '—' }}
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="3" style="padding-top: 10px;">
                                <div class="label">Descripción del servicio</div>
                                <div class="value">{{ $invoice->service_name }}</div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding-top: 10px;">
                                <div class="label">Período desde</div>
                                <div class="value">
                                    {{ $invoice->service_period_start?->format('d/m/Y') ?: '—' }}
                                </div>
                            </td>

                            <td style="padding-top: 10px;">
                                <div class="label">Período hasta</div>
                                <div class="value">
                                    {{ $invoice->service_period_end?->format('d/m/Y') ?: '—' }}
                                </div>
                            </td>

                            <td style="padding-top: 10px;">
                                <div class="label">Estado de pago</div>
                                <div class="value">
                                    {{ $pagada ? 'Pagada' : 'Pendiente de pago' }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

            @if ($letra)
                <td style="width: 76px; padding-left: 10px;">
                    <div class="letter">{{ $letra }}</div>

                    <div class="small muted center" style="margin-top: 4px;">
                        Código {{ str_pad((string) $tipoComprobante, 2, '0', STR_PAD_LEFT) }}
                    </div>
                </td>
            @endif
        </tr>
    </table>

    {{-- Leyenda de retención --}}
    @if ($leyendaComprobante)
        <div class="retention-legend">
            {{ $leyendaComprobante }}
        </div>
    @endif

    {{-- Estado de pago --}}
    <div class="center" style="margin: 16px 0;">
        @if ($pagada)
            <span class="stamp">PAGADA</span>
        @else
            <span class="stamp stamp-pending">PENDIENTE DE PAGO</span>
        @endif
    </div>

    {{-- Datos fiscales del receptor --}}
    <div class="box" style="margin-bottom: 14px;">
        <p class="section-title">
            {{ $esExportacion ? 'Datos del comprador extranjero' : 'Datos del cliente' }}
        </p>

        <table>
            <tr>
                <td style="width: 38%; padding-right: 8px;">
                    <div class="label">Nombre o razón social</div>
                    <div class="value">{{ $clientName ?: '—' }}</div>
                </td>

                <td style="width: 32%; padding-right: 8px;">
                    <div class="label">{{ $clientDocumentLabel }}</div>
                    <div class="value">{{ $clientDocument ?: '—' }}</div>
                </td>

                <td style="width: 30%;">
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
                    <td colspan="3" style="padding-top: 10px;">
                        <div class="label">Domicilio del comprador</div>
                        <div class="value">{{ $clientAddress ?: '—' }}</div>
                    </td>
                </tr>

                @if ($clientForeignTaxId || $clientIdImpositivo)
                    <tr>
                        <td colspan="3" style="padding-top: 10px;">
                            <div class="label">Identificación fiscal extranjera</div>
                            <div class="value">
                                {{ $clientForeignTaxId ?: $clientIdImpositivo }}
                            </div>
                        </td>
                    </tr>
                @endif
            @else
                <tr>
                    <td colspan="3" style="padding-top: 10px;">
                        <div class="label">Condición frente al IVA</div>
                        <div class="value">
                            {{ $codigoIva }} — {{ $condicionIva }}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding-top: 10px;">
                        <div class="label">Teléfono</div>
                        <div class="value">{{ $client?->phone ?: '—' }}</div>
                    </td>

                    <td colspan="2" style="padding-top: 10px;">
                        <div class="label">Email</div>
                        <div class="value">{{ $client?->email ?: '—' }}</div>
                    </td>
                </tr>

                <tr>
                    <td colspan="3" style="padding-top: 10px;">
                        <div class="label">Dirección</div>
                        <div class="value">
                            {{ $clientAddress ?: '—' }}
                        </div>
                    </td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Detalle de la operación --}}
    <table class="items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="width: 22%;">Período</th>
                <th style="width: 16%;">Vencimiento</th>
                <th class="right" style="width: 19%;">Importe</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>
                    {{ $invoice->service_name }}

                    @if ($esExportacion)
                        <div class="small muted" style="margin-top: 4px;">
                            Servicio de exportación
                        </div>
                    @endif
                </td>

                <td>
                    {{ $invoice->service_period_start?->format('d/m/Y') ?: '—' }}
                    –
                    {{ $invoice->service_period_end?->format('d/m/Y') ?: '—' }}
                </td>

                <td>
                    {{ $invoice->due_date?->format('d/m/Y') ?: '—' }}
                </td>

                <td class="right">
                    {{ $simboloMoneda }}{{ number_format($neto, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="totals" style="margin-top: 10px; width: 54%; margin-left: 46%;">
        <tr>
            <td>Subtotal</td>
            <td class="right">
                {{ $simboloMoneda }}{{ number_format($neto, 2, ',', '.') }}
            </td>
        </tr>

        @unless ($esExportacion)
            <tr>
                <td>
                    IVA
                    {{ rtrim(rtrim(number_format($tasa, 2, '.', ''), '0'), '.') }}%
                </td>
                <td class="right">
                    {{ $simboloMoneda }}{{ number_format($iva, 2, ',', '.') }}
                </td>
            </tr>
        @endunless

        <tr class="grand">
            <td>Total ({{ $monedaCodigo }})</td>
            <td class="right">
                {{ $simboloMoneda }}{{ number_format($total, 2, ',', '.') }}
            </td>
        </tr>
    </table>

    @if ($esExportacion)
        <div class="export-note small">
            Moneda del comprobante: {{ $monedaCodigo }}.
            El detalle de impuestos se presenta según la clasificación
            de la operación de exportación.
        </div>
    @endif

    {{-- Recibo de pago --}}
    @if ($pagada)
        <div class="box" style="margin-top: 18px;">
            <p class="section-title">Recibo de pago</p>

            <table>
                <tr>
                    <td style="width: 25%; padding-right: 8px;">
                        <div class="label">Fecha de pago</div>
                        <div class="value">
                            {{ $invoice->paid_at?->format('d/m/Y') ?: '—' }}
                        </div>
                    </td>

                    <td style="width: 25%; padding-right: 8px;">
                        <div class="label">Medio de pago</div>
                        <div class="value">{{ $metodoPago ?: '—' }}</div>
                    </td>

                    <td style="width: 25%; padding-right: 8px;">
                        <div class="label">Importe abonado</div>
                        <div class="value">
                            {{ $simboloMoneda }}{{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}
                        </div>
                    </td>

                    <td style="width: 25%;">
                        <div class="label">N.º de operación</div>
                        <div class="value">
                            {{ $invoice->mercadopago_payment_id ?: '—' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @else
        <p class="small muted" style="margin-top: 18px;">
            El comprobante fiscal con CAE se emite una vez confirmado el pago.
        </p>
    @endif

    {{-- CAE y QR --}}
    @if ($conCae)
        <table class="cae-section">
            <tr>
                <td style="width: 125px;">
                    @if ($qr)
                        <img
                            src="{{ $qr }}"
                            alt="Código QR del comprobante"
                            style="width: 110px; height: 110px;"
                        >
                    @endif
                </td>

                <td style="padding-left: 12px;">
                    <div class="label">CAE</div>
                    <div class="value">{{ $invoice->arca_cae }}</div>

                    <div class="label" style="margin-top: 8px;">
                        Vencimiento del CAE
                    </div>

                    <div class="value">
                        {{ $invoice->arca_cae_expires_at?->format('d/m/Y') ?: '—' }}
                    </div>

                    <p class="small muted" style="margin-top: 10px;">
                        Comprobante autorizado por ARCA.
                    </p>
                </td>
            </tr>
        </table>
    @elseif ($invoice->arca_status)
        <div class="box" style="margin-top: 18px;">
            <p class="section-title">Estado de la emisión fiscal</p>
            <p>{{ $invoice->arca_status }}</p>
        </div>
    @endif

</body>
</html>