<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceTreasuryTransaction extends Model
{
    protected $table = 'finance_treasury_transactions';

    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    public function account()
    {
        return $this->belongsTo(
            \Modules\Finance\Entities\Account::class,
            'account_id'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }
}