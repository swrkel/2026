<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRecoveryPriorityScore extends Model
{
    protected $table = 'loan_recovery_priority_scores';

    protected $guarded = [];

    public function loan()
    {
        return $this->belongsTo(
            Loan::class,
            'loan_id'
        );
    }

    public function customer()
    {
        return $this->belongsTo(
            \App\Contact::class,
            'customer_id'
        );
    }

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