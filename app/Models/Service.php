<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'service',
        'price',
        'due_day',
        'overdue_price',
        'period',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'due_day' => 'integer',
            'overdue_price' => 'decimal:2',
            'period' => 'integer',
        ];
    }

    /**
     * Clientes que tienen contratado este servicio.
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(
            Client::class,
            'client_services'
        )->withTimestamps();
    }

    /**
     * Facturas generadas para este servicio.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}