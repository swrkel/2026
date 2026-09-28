<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletAdjustment extends Model
{
    protected $table = 'digital_wallet_adjustments';
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:6',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(DigitalWallet::class, 'wallet_id');
    }
}
