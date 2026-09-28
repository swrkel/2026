<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletTransfer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:6',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function fromWallet()
    {
        return $this->belongsTo(DigitalWallet::class, 'from_wallet_id');
    }

    public function toWallet()
    {
        return $this->belongsTo(DigitalWallet::class, 'to_wallet_id');
    }
}
