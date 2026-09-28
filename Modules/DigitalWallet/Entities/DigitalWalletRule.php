<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletRule extends Model
{
    protected $table = 'digital_wallet_rules';
    protected $guarded = ['id'];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'minimum_balance' => 'decimal:6',
        'maximum_balance' => 'decimal:6',
        'daily_limit' => 'decimal:6',
        'monthly_limit' => 'decimal:6',
        'approval_threshold' => 'decimal:6',
    ];
}
