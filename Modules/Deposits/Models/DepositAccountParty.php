<?php

namespace Modules\Deposits\Models;

use Illuminate\Database\Eloquent\Model;

class DepositAccountParty extends Model
{
    protected $table = 'deposit_account_parties';
    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(DepositAccount::class, 'deposit_account_id');
    }
}
