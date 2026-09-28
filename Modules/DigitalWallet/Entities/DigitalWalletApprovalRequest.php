<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletApprovalRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:6',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];
}
