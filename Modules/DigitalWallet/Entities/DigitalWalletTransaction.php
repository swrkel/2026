<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletTransaction extends Model
{
    protected $table = 'digital_wallet_transactions';
    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:6',
        'balance_before' => 'decimal:6',
        'balance_after' => 'decimal:6',
    ];

    public function wallet()
    {
        return $this->belongsTo(DigitalWallet::class, 'wallet_id');
    }
}
