<?php

namespace Modules\Deposits\Models;

use Illuminate\Database\Eloquent\Model;

class DepositTransaction extends Model
{
    protected $table = 'deposit_transactions';
    protected $guarded = ['id'];

    protected $dates = ['transaction_date'];

    public function account()
    {
        return $this->belongsTo(DepositAccount::class, 'deposit_account_id');
    }
}
