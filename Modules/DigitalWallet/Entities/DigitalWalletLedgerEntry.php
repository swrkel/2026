<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletLedgerEntry extends Model
{
    protected $table = 'digital_wallet_ledger_entries';
    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'entry_date' => 'datetime',
        'debit' => 'decimal:6',
        'credit' => 'decimal:6',
        'balance' => 'decimal:6',
    ];
}
