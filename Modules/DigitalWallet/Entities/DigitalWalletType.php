<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
}
