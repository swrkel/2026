<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceBranchScore extends Model
{
    protected $table = 'finance_branch_scores';

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
}