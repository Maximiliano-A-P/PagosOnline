<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientService extends Model
{
    protected $fillable = [
        'client_id',
        'service_id',
    ];

    /**
     * Cliente relacionado.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Servicio relacionado.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}