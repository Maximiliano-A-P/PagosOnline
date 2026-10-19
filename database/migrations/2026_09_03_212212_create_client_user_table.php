<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento que un usuario agregó para consultar sus facturas.
 *
 * Se guarda el número de documento (no el id del cliente): las facturas
 * se relacionan por documento, así que el usuario las sigue viendo
 * aunque el cliente se bloquee o se elimine.
 */
class UserDocument extends Model
{
    protected $table = 'client_user';

    protected $fillable = [
        'user_id',
        'document',
    ];

    protected function casts(): array
    {
        return [
            'document' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}