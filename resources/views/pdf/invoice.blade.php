<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura</title>

    <style>
        @page { margin: 28px 32px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .box {
            border: 1px solid #9ca3af;
            padding: 10px 12px;
        }

        .title { font-size: 20px; font-weight: bold; margin: 0; }
        .muted { color: #6b7280; }
        .label { font-size: 9px; color: #6b7280; margin-bottom: 2px; }
        .value { font-size: 11px; font-weight: bold; }

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

        .items th {
            background: #111827;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
        }

        .items td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .right { text-align: right; }

        .totals td { padding: 4px 8px; }
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
            font-size: 22px;
            font-weight: bold;
            padding: 4px 16px;
        }

        .stamp-pending {
            border-color: #b45309;
            color: #b45309;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 6px 0;
        }

        .small { font-size: 9px; }
    </style>
</head>

<body>

    {{-- ====================== Encabezado ====================== --}}
    <table>
        <tr>
            <td style="width: 42%;">
                <div class="box">
                    <p class="title">{{ $emisorNombre }}</p>
                    @if ($emisorCuit)
                        <div class="small">CUIT: {{ $emisorCuit }}</div>
                    @endif
                    @if ($emisorCondicion)
                        <div class="small">{{ $emisorCondicion }}</div>
                    @endif
                </div>
            </td>

            <td style="width: 16%; padding: 0 8px;">
                @if ($letra)
                    <div class="letter">{{ $letra }}</div>
                    <div class="small muted" style="text-align:center; margin-top:3px;">
                        Cód. {{ str_pad((string) $invoice->arca_invoice_type, 2, '0', STR_PAD_LEFT) }}
                    </div>
                @endif
            </td>

            <td style="width: 42%;">
                <div class="box">
                    <p class="title">
                        {{ $conCae ? 'FACTURA' : 'DETALLE DE FACTURA' }}
                    </p>

                    @if ($numero)
                        <div class="value">N° {{ $numero }}</div>
                    @endif

                    <div class="small">
                        Fecha de emisión: {{ $invoice->issued_at->format('d/m/Y') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>


    {{-- ====================== Estado ====================== --}}
    <div style="margin: 16px 0; text-align: center;">
        @if ($pagada)
            <span class="stamp">PAGADA</span>
        @else
            <span class="stamp stamp-pending">PENDIENTE DE PAGO</span>
        @endif
    </div>


    {{-- ====================== Cliente ====================== --}}
    <div class="box" style="margin-bottom: 14px;">
        <table>
            <tr>
                <td style="width: 40%;">
                    <div class="label">Cliente</div>
                    <div class="value">{{ $invoice->client_name }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="label">Documento</div>
                    <div class="value">{{ $invoice->client_document }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="label">CUIT</div>
                    <div class="value">{{ $invoice->client_cuit ?: '—' }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="label">Condición frente al IVA</div>
                    <div class="value">{{ $condicionIva }}</div>
                </td>
            </tr>
        </table>
    </div>


    {{-- ====================== Detalle ====================== --}}
    <table class="items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="width: 22%;">Período</th>
                <th style="width: 16%;">Vencimiento</th>
                <th class="right" style="width: 16%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->service_name }}</td>
                <td>
                    {{ $invoice->service_period_start?->format('d/m/Y') }}
                    –
                    {{ $invoice->service_period_end?->format('d/m/Y') }}
                </td>
                <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                <td class="right">${{ number_format($neto, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals" style="margin-top: 10px; width: 50%; margin-left: 50%;">
        <tr>
            <td>Subtotal neto</td>
            <td class="right">${{ number_format($neto, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>IVA {{ rtrim(rtrim(number_format($tasa, 2, '.', ''), '0'), '.') }}%</td>
            <td class="right">${{ number_format($iva, 2, ',', '.') }}</td>
        </tr>
        <tr class="grand">
            <td>Total</td>
            <td class="right">${{ number_format($total, 2, ',', '.') }}</td>
        </tr>
    </table>


    {{-- ====================== Recibo de pago ====================== --}}
    @if ($pagada)
        <div class="box" style="margin-top: 18px;">
            <p class="section-title">Recibo de pago</p>

            <table>
                <tr>
                    <td style="width: 25%;">
                        <div class="label">Fecha de pago</div>
                        <div class="value">{{ $invoice->paid_at?->format('d/m/Y') }}</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="label">Medio de pago</div>
                        <div class="value">{{ $metodoPago ?: '—' }}</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="label">Importe abonado</div>
                        <div class="value">
                            ${{ number_format((float) $invoice->amount_paid, 2, ',', '.') }}
                        </div>
                    </td>
                    <td style="width: 25%;">
                        <div class="label">N° de operación</div>
                        <div class="value">{{ $invoice->mercadopago_payment_id ?: '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    @else
        <p class="small muted" style="margin-top: 18px;">
            El comprobante fiscal con CAE se emite una vez confirmado el pago.
        </p>
    @endif


    {{-- ====================== CAE y QR ====================== --}}
    @if ($conCae)
        <table style="margin-top: 18px;">
            <tr>
                <td style="width: 120px;">
                    @if ($qr)
                        <img src="{{ $qr }}" style="width: 110px; height: 110px;">
                    @endif
                </td>

                <td style="padding-left: 12px;">
                    <div class="label">CAE N°</div>
                    <div class="value">{{ $invoice->arca_cae }}</div>

                    <div class="label" style="margin-top: 8px;">Vencimiento del CAE</div>
                    <div class="value">
                        {{ $invoice->arca_cae_expires_at?->format('d/m/Y') ?: '—' }}
                    </div>

                    <p class="small muted" style="margin-top: 10px;">
                        Comprobante autorizado por ARCA. Escaneá el código QR
                        para verificarlo.
                    </p>
                </td>
            </tr>
        </table>
    @endif

</body>
</html>
