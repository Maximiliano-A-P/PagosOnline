<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'document',
    ];

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
}