<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArcaConfig extends Model
{
    protected $table = 'arca_config';

    protected $fillable = [
        'condicion_iva',
        'tipo_factura_a',

        'punto_venta',
        'punto_venta_exportacion',

        // WSFEv1.
        'token',
        'sign',
        'token_expires_at',

        // WSFEXv1.
        'token_wsfex',
        'sign_wsfex',
        'token_wsfex_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'condicion_iva' => 'integer',
            'tipo_factura_a' => 'integer',

            'punto_venta' => 'integer',
            'punto_venta_exportacion' => 'integer',

            'token_expires_at' => 'datetime',
            'token_wsfex_expires_at' => 'datetime',
        ];
    }
}