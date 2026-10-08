<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'document',
        'cuit',
        'arca_iva_condition',
        'phone',
        'email',
        'address',
        'blocked',
        'blocked_at',
    ];

    protected function casts(): array
    {
        return [
            'blocked' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    /**
     * Los clientes bloqueados quedan fuera de TODAS las consultas
     * normales de la aplicación (listados, búsquedas, generación de
     * facturas, binding de rutas, etc.). Solo se pueden ver desde la
     * base de datos o usando withoutGlobalScopes().
     */
    protected static function booted(): void
    {
        static::addGlobalScope('not_blocked', function (Builder $query) {
            $query->where($query->getModel()->getTable() . '.blocked', false);
        });
    }

    /**
     * Servicios que tiene asignados el cliente.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'client_services'
        );
    }

    /**
     * Asignaciones del cliente a servicios.
     */
    public function clientServices(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    /**
     * Usuarios que tienen acceso a este cliente.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'client_user'
        );
    }
}