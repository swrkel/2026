<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletReservation extends Model
{
    protected $table = 'digital_wallet_reservations';
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:6',
        'expires_at' => 'datetime',
        'committed_at' => 'datetime',
        'released_at' => 'datetime',
        'meta' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(DigitalWallet::class, 'wallet_id');
    }

    public function transaction()
    {
        return $this->belongsTo(DigitalWalletTransaction::class, 'transaction_id');
    }
}
