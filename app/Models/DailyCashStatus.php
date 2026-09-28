<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCashStatus extends Model
{
    use HasFactory;

    protected $table = 'petro_daily_cash_status';

    protected $fillable = [
        'cash_collection',
        'customer_payment_cash',
        'cash_expenses',
        'cash_deposit',
        'cash_total_given',
        'balance_in_hand',
        'shift',
        'cash_transaction_date',
        'notes'
    ];

    public function settlements()
    {
        return $this->hasMany(CashSettlement::class);
    }

    // Calculate balance in hand
    public function calculateBalance()
    {
        return ($this->cash_collection + $this->customer_payment_cash)
            - $this->cash_expenses
            - $this->cash_deposit
            - $this->cash_total_given;
    }
}