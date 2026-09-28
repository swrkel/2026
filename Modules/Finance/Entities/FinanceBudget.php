<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceBudget extends Model
{
    protected $table = 'finance_budgets';

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

    public function approvedBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'approved_by'
        );
    }
}