<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPenaltyTransaction extends Model
{
    protected $table =
        'loan_penalty_transactions';

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
    | Penalty Relationship
    |--------------------------------------------------------------------------
    */

    public function penalty()
    {
        return $this->belongsTo(
            LoanPenalty::class,
            'loan_penalty_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Active Penalties
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'status',
            'active'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Waived Penalties
    |--------------------------------------------------------------------------
    */

    public function scopeWaived($query)
    {
        return $query->where(
            'status',
            'waived'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Paid Penalties
    |--------------------------------------------------------------------------
    */

    public function scopePaid($query)
    {
        return $query->where(
            'status',
            'paid'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor: Formatted Amount
    |--------------------------------------------------------------------------
    */

    public function getFormattedAmountAttribute()
    {
        return number_format(
            $this->amount,
            2
        );
    }
}