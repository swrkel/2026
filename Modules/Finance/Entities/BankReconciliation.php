<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliation extends Model
{
    use SoftDeletes;

    protected $table = 'finance_bank_reconciliations';

    protected $guarded = ['id'];

    protected $dates = [
        'statement_date',
        'reconciled_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'statement_ending_balance' => 'decimal:4',
        'book_ending_balance' => 'decimal:4',
        'outstanding_deposits' => 'decimal:4',
        'outstanding_payments' => 'decimal:4',
        'adjusted_bank_balance' => 'decimal:4',
        'difference' => 'decimal:4',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function lines()
    {
        return $this->hasMany(BankReconciliationLine::class, 'reconciliation_id');
    }
}
