<?php
namespace Modules\AirlineTicketingNew\Entities;

class B2bWalletTransaction extends BaseAirlineTicketingModel
{
    protected $table = 'atn_b2b_wallet_transactions';
    protected $guarded = ['id'];
    protected $casts = [
        'transaction_date' => 'datetime',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];
}
