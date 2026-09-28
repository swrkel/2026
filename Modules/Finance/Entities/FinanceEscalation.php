<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;

class FinanceEscalation extends Model
{
    protected $table = 'finance_escalations';

    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    public function assignedUser()
    {
        return $this->belongsTo(
            \App\User::class,
            'assigned_to'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }

    public function resolvedBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'resolved_by'
        );
    }
}