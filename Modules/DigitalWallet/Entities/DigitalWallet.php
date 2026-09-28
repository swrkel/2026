<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalWallet extends Model
{
    use SoftDeletes;

    protected $table = 'digital_wallets';
    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
        'available_balance' => 'decimal:6',
        'reserved_balance' => 'decimal:6',
        'total_balance' => 'decimal:6',
        'credit_limit' => 'decimal:6',
        'low_balance_threshold' => 'decimal:6',
        'daily_spend_limit' => 'decimal:6',
        'monthly_spend_limit' => 'decimal:6',
        'is_locked' => 'boolean',
    ];

    public function parentWallet()
    {
        return $this->belongsTo(self::class, 'parent_wallet_id');
    }

    public function childWallets()
    {
        return $this->hasMany(self::class, 'parent_wallet_id');
    }

    public function transactions()
    {
        return $this->hasMany(DigitalWalletTransaction::class, 'wallet_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(DigitalWalletLedgerEntry::class, 'wallet_id');
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(DigitalWalletTransfer::class, 'from_wallet_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(DigitalWalletTransfer::class, 'to_wallet_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_locked', false);
    }
}
