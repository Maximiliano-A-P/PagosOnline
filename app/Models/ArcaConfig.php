<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArcaConfig extends Model
{
    protected $table = 'arca_config';

    protected $fillable = [
        'cuit',
        'certificate_path',
        'private_key_path',
        'token',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }
}