<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $table = 'loan_repayments';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Loan Relationship
    |--------------------------------------------------------------------------
    */

    public function loan()
    {
        return $this->belongsTo(
            LoanApplication::class,
            'loan_application_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Schedule Relationship
    |--------------------------------------------------------------------------
    */

    public function schedule()
    {
        return $this->belongsTo(
            LoanSchedule::class,
            'loan_schedule_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Completed Repayments
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted($query)
    {
        return $query->where(
            'status',
            'completed'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Pending Repayments
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where(
            'status',
            'pending'
        );
    }
}