<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'issued_at',

        // Datos históricos del cliente.
        'client_name',
        'client_document',
        'client_cuit',
        'client_document_type',
        'client_iva_condition',

        // Snapshot fiscal de exportación.
        'client_address',
        'client_arca_destination_country',
        'client_arca_cuit_pais_cliente',
        'client_arca_id_impositivo',

        // Identificación y datos históricos del servicio.
        'service_id',
        'service_name',
        'service_period_start',
        'service_period_end',

        // Datos económicos.
        'price',
        'due_date',
        'overdue_price',
        'tax_percentage',
        'expected_amount',

        // Estado del pago.
        'payment_status',
        'amount_paid',
        'paid_at',
        'payment_method',
        'paid_by',

        // Mercado Pago.
        'mercadopago_preference_id',
        'mercadopago_payment_id',

        // ARCA.
        'arca_status',
        'arca_cae',
        'arca_cae_expires_at',
        'arca_invoice_type',
        'arca_point_of_sale',
        'arca_invoice_number',
        'arca_qr',
        'arca_retry_attempts',
        'arca_last_attempt_at',
        'arca_retry_at',
        'receipt_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',

            'client_document' => 'integer',
            'client_document_type' => 'integer',
            'client_iva_condition' => 'integer',
            'client_arca_destination_country' => 'integer',

            'service_id' => 'integer',
            'service_period_start' => 'date',
            'service_period_end' => 'date',

            'price' => 'decimal:2',
            'due_date' => 'date',
            'overdue_price' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'expected_amount' => 'decimal:2',

            'amount_paid' => 'decimal:2',
            'paid_at' => 'date',

            'arca_cae_expires_at' => 'datetime',
            'arca_point_of_sale' => 'integer',
            'arca_invoice_number' => 'integer',
            'arca_retry_attempts' => 'integer',
            'arca_last_attempt_at' => 'datetime',
            'arca_retry_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    private function tasaImpuesto(): float
    {
        return (float) ($this->tax_percentage ?? 0) / 100;
    }

    public function totalNormal(): float
    {
        return round(
            (float) $this->price * (1 + $this->tasaImpuesto()),
            2
        );
    }

    public function totalVencido(): float
    {
        return round(
            (float) $this->overdue_price * (1 + $this->tasaImpuesto()),
            2
        );
    }

    public function recargoPorMora(): float
    {
        return max(
            0.0,
            round($this->totalVencido() - $this->totalNormal(), 2)
        );
    }

    public function fechaAumento(): Carbon
    {
        return $this->due_date->copy()->startOfDay()->addDay();
    }

    public function estaVencidaAl(Carbon $fecha): bool
    {
        return $fecha->copy()->startOfDay()->gte($this->fechaAumento());
    }

    public function montoAl(Carbon $fecha): float
    {
        return $this->estaVencidaAl($fecha)
            ? $this->totalVencido()
            : $this->totalNormal();
    }

    public function estaVencida(): bool
    {
        return $this->payment_status !== 'paid'
            && $this->estaVencidaAl(now());
    }

    public function montoACobrar(): float
    {
        if ($this->payment_status === 'paid') {
            return (float) $this->amount_paid;
        }

        return $this->montoAl(now());
    }

    /**
     * Movimientos contables de la factura.
     */
    public function movimientos(): array
    {
        $pagada = $this->payment_status === 'paid';

        $movimientos = [[
            'fecha' => $this->issued_at->copy()->startOfDay(),
            'orden' => 0,
            'id' => $this->id,
            'detalle' => "Factura #{$this->id} — {$this->service_name}",
            'debe' => $this->totalNormal(),
            'haber' => 0.0,
        ]];

        $fechaPago = $pagada
            ? ($this->paid_at ?? $this->issued_at)->copy()->startOfDay()
            : null;

        $recargo = $this->recargoPorMora();

        if (
            $recargo > 0
            && (! $pagada || $fechaPago->gte($this->fechaAumento()))
        ) {
            $movimientos[] = [
                'fecha' => $this->fechaAumento(),
                'orden' => 0,
                'id' => $this->id,
                'detalle' => "Aumento por vencimiento — Factura #{$this->id}",
                'debe' => $recargo,
                'haber' => 0.0,
            ];
        }

        if ($pagada) {
            $movimientos[] = [
                'fecha' => $fechaPago,
                'orden' => 1,
                'id' => $this->id,
                'detalle' => "Pago factura #{$this->id}",
                'debe' => 0.0,
                'haber' => round((float) $this->amount_paid, 2),
            ];
        }

        return $movimientos;
    }

    public function saldoAl(Carbon $fecha): float
    {
        $fecha = $fecha->copy()->startOfDay();

        return round(
            collect($this->movimientos())
                ->filter(fn ($m) => $m['fecha']->lte($fecha))
                ->sum(fn ($m) => $m['debe'] - $m['haber']),
            2
        );
    }
}