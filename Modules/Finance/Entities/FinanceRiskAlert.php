<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceRiskAlert extends Model
{
    protected $table = 'finance_risk_alerts';

    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }

    public function reviewedBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'reviewed_by'
        );
    }
}