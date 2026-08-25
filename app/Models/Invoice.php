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

        // Estado del pago
        'payment_status',
        'amount_paid',
        'paid_at',
        'payment_method',
        'paid_by',

        // Datos ARCA
        'arca_status',
        'arca_cae',
        'arca_cae_expires_at',
        'arca_invoice_type',
        'arca_point_of_sale',
        'arca_invoice_number',
        'arca_qr',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',

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
            'paid_at' => 'datetime',

            // Datos ARCA
            'arca_cae_expires_at' => 'datetime',
            'arca_point_of_sale' => 'integer',
            'arca_invoice_number' => 'integer',
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
}