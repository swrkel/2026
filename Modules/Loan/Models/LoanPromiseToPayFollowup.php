<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPromiseToPayFollowup extends Model
{
    protected $table = 'loan_promise_to_pay_followups';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Promise To Pay
    |--------------------------------------------------------------------------
    */

    public function promiseToPay()
    {
        return $this->belongsTo(
            LoanPromiseToPay::class,
            'promise_to_pay_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned Officer
    |--------------------------------------------------------------------------
    */

    public function assignedOfficer()
    {
        return $this->belongsTo(
            \App\User::class,
            'assigned_to'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Creator
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Pending
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where(
            'followup_status',
            'pending'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Completed
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted($query)
    {
        return $query->where(
            'followup_status',
            'completed'
        );
    }
}