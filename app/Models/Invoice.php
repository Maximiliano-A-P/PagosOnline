<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'issued_at',

        // Datos históricos del cliente
        'client_name',
        'client_document',

        // Identificación y datos históricos del servicio
        'service_id',
        'service_name',

        // Datos económicos
        'price',
        'due_date',
        'overdue_price',
        'tax_percentage',

        // Estado del pago
        'payment_status',
        'amount_paid',
        'paid_at',
        'payment_method',
        'paid_by',

        // Datos Mercado Pago
        'mercadopago_preference_id',
        'mercadopago_payment_id',
        'expected_amount' => 'decimal:2',

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

    /**
     * True si, a día de hoy, corresponde el precio con recargo
     * por mora (todavía no fue pagada y ya venció).
     */
    public function estaVencida(): bool
    {
        return $this->payment_status !== 'paid'
            && now()->greaterThan($this->due_date);
    }

    /**
     * Monto que corresponde cobrar en este momento: el ya pagado
     * si la factura está paga, o una previsión (precio normal o
     * vencido, según corresponda) + IVA si todavía está pendiente.
     */
    public function montoACobrar(): float
    {
        if ($this->payment_status === 'paid') {
            return (float) $this->amount_paid;
        }

        $base = $this->estaVencida()
            ? (float) $this->overdue_price
            : (float) $this->price;

        $iva = round($base * (float) ($this->tax_percentage ?? 0) / 100, 2);

        return $base + $iva;
    }
}