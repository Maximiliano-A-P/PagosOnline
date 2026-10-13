<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'issued_at',

        // Datos históricos del cliente
        'client_name',
        'client_document',
        'client_cuit',

        // Identificación y datos históricos del servicio
        'service_id',
        'service_name',

        // Datos económicos
        'price',
        'due_date',
        'overdue_price',
        'tax_percentage',
        'expected_amount',

        // Estado del pago
        'payment_status',
        'amount_paid',
        'paid_at',
        'payment_method',
        'paid_by',

        // Datos Mercado Pago
        'mercadopago_preference_id',
        'mercadopago_payment_id',

        // Datos ARCA
        'arca_status',
        'arca_cae',
        'arca_cae_expires_at',
        'arca_invoice_type',
        'arca_point_of_sale',
        'arca_invoice_number',
        'arca_qr',
        'client_document_type',
        'client_iva_condition',
        'service_period_start',
        'service_period_end',
        'arca_retry_attempts',
        'arca_last_attempt_at',
        'arca_retry_at',
        'receipt_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',

            // Datos del cliente
            'client_document' => 'integer',

            // Servicio asociado
            // Puede ser NULL en facturas históricas/manuales.
            'service_id' => 'integer',

            // Datos económicos
            'price' => 'decimal:2',
            'due_date' => 'date',
            'overdue_price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'date',
            'expected_amount' => 'decimal:2',

            // Datos ARCA
            'arca_cae_expires_at' => 'datetime',
            'arca_point_of_sale' => 'integer',
            'arca_invoice_number' => 'integer',
            'service_period_start' => 'date',
            'service_period_end' => 'date',
            'tax_percentage' => 'decimal:2',
            'arca_retry_attempts' => 'integer',
            'arca_last_attempt_at' => 'datetime',
            'arca_retry_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
        ];
    }

    /**
     * Servicio asociado a la factura.
     *
     * Puede ser NULL cuando se trata de una factura histórica
     * cuyo servicio ya no existe o no está asociado al sistema.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class,
            'service_id'
        );
    }

    /**
     * Usuario que registró manualmente el pago.
     *
     * Puede ser NULL cuando el pago todavía no fue registrado.
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'paid_by'
        );
    }

    /*
     * ------------------------------------------------------------
     * Valores de la factura: normal y vencido (siempre + impuestos)
     * ------------------------------------------------------------
     */

    private function tasaImpuesto(): float
    {
        return (float) ($this->tax_percentage ?? 0) / 100;
    }

    /** Total con precio normal + impuestos. */
    public function totalNormal(): float
    {
        return round((float) $this->price * (1 + $this->tasaImpuesto()), 2);
    }

    /** Total con precio vencido + impuestos. */
    public function totalVencido(): float
    {
        return round((float) $this->overdue_price * (1 + $this->tasaImpuesto()), 2);
    }

    /** Diferencia entre el total vencido y el normal (nunca negativa). */
    public function recargoPorMora(): float
    {
        return max(0.0, round($this->totalVencido() - $this->totalNormal(), 2));
    }

    /** Primer día en que rige el precio vencido (día siguiente al vencimiento). */
    public function fechaAumento(): Carbon
    {
        return $this->due_date->copy()->startOfDay()->addDay();
    }

    /** True si en esa fecha ya rige el precio vencido. */
    public function estaVencidaAl(Carbon $fecha): bool
    {
        return $fecha->copy()->startOfDay()->gte($this->fechaAumento());
    }

    /** Importe (con impuestos) que corresponde pagar en esa fecha. */
    public function montoAl(Carbon $fecha): float
    {
        return $this->estaVencidaAl($fecha)
            ? $this->totalVencido()
            : $this->totalNormal();
    }

    /**
     * True si, a día de hoy, corresponde el precio con recargo
     * por mora (todavía no fue pagada y ya venció).
     */
    public function estaVencida(): bool
    {
        return $this->payment_status !== 'paid'
            && $this->estaVencidaAl(now());
    }

    /**
     * Monto que corresponde cobrar en este momento: el ya pagado
     * si la factura está paga, o el normal / vencido (+ impuestos)
     * según la fecha de hoy si todavía está pendiente.
     */
    public function montoACobrar(): float
    {
        if ($this->payment_status === 'paid') {
            return (float) $this->amount_paid;
        }

        return $this->montoAl(now());
    }

    /**
     * Movimientos contables de la factura (única fuente de verdad
     * para el asiento del cliente y para el saldo del index).
     *
     * - Factura:  debe = total normal (con impuestos), en la emisión.
     * - Aumento:  debe = recargo por vencimiento, el primer día vencida.
     *             Si ya está paga, solo si se pagó vencida.
     * - Pago:     haber = importe pagado, en la fecha de pago.
     */
    public function movimientos(): array
    {
        $pagada = $this->payment_status === 'paid';

        $movimientos = [[
            'fecha'   => $this->issued_at->copy()->startOfDay(),
            'orden'   => 0,
            'id'      => $this->id,
            'detalle' => "Factura #{$this->id} — {$this->service_name}",
            'debe'    => $this->totalNormal(),
            'haber'   => 0.0,
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
                'fecha'   => $this->fechaAumento(),
                'orden'   => 0,
                'id'      => $this->id,
                'detalle' => "Aumento por vencimiento — Factura #{$this->id}",
                'debe'    => $recargo,
                'haber'   => 0.0,
            ];
        }

        if ($pagada) {
            $movimientos[] = [
                'fecha'   => $fechaPago,
                'orden'   => 1,
                'id'      => $this->id,
                'detalle' => "Pago factura #{$this->id}",
                'debe'    => 0.0,
                'haber'   => round((float) $this->amount_paid, 2),
            ];
        }

        return $movimientos;
    }

    /** Saldo de esta factura a una fecha (positivo = el cliente debe). */
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