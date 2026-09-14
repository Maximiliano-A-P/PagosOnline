<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArcaConfig extends Model
{
    protected $table = 'arca_config';

    protected $fillable = [
        'cuit',
        'condicion_iva',
        'punto_venta',
        'certificate_path',
        'private_key_path',
        'token',
        'sign',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'condicion_iva' => 'integer',
            'punto_venta' => 'integer',
            'token_expires_at' => 'datetime',
        ];
    }
}