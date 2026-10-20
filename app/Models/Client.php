<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'document',
        'cuit',
        'arca_iva_condition',

        // Datos fiscales del comprador extranjero.
        'arca_destination_country',
        'arca_cuit_pais_cliente',
        'arca_id_impositivo',

        // Contacto y estado.
        'phone',
        'email',
        'address',
        'blocked',
        'blocked_at',
    ];

    protected function casts(): array
    {
        return [
            'arca_iva_condition' => 'integer',
            'arca_destination_country' => 'integer',
            'blocked' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    /**
     * Los clientes bloqueados se excluyen de las consultas normales.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('not_blocked', function (Builder $query) {
            $query->where(
                $query->getModel()->getTable() . '.blocked',
                false
            );
        });
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'client_services'
        );
    }

    public function clientServices(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }
}