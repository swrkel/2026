<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class BankReconciliationLine extends Model
{
    protected $table = 'finance_bank_reconciliation_lines';

    protected $guarded = ['id'];

    protected $dates = [
        'transaction_date',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'is_cleared' => 'boolean',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function accountTransaction()
    {
        return $this->belongsTo(AccountTransaction::class, 'account_transaction_id');
    }
}
