<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPromiseToPay extends Model
{
    protected $table = 'loan_promise_to_pay';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Loan
    |--------------------------------------------------------------------------
    */

    public function loan()
    {
        return $this->belongsTo(
            Loan::class,
            'loan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(
            \App\Contact::class,
            'customer_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recovery Officer
    |--------------------------------------------------------------------------
    */

    public function recoveryOfficer()
    {
        return $this->belongsTo(
            \App\User::class,
            'recovery_officer_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Branch / Location
    |--------------------------------------------------------------------------
    */

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */

    public function logs()
    {
        return $this->hasMany(
            LoanPromiseToPayLog::class,
            'promise_to_pay_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Followups
    |--------------------------------------------------------------------------
    */

    public function followups()
    {
        return $this->hasMany(
            LoanPromiseToPayFollowup::class,
            'promise_to_pay_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Open
    |--------------------------------------------------------------------------
    */

    public function scopeOpen($query)
    {
        return $query->where(
            'status',
            'open'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Broken
    |--------------------------------------------------------------------------
    */

    public function scopeBroken($query)
    {
        return $query->where(
            'status',
            'broken'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Kept
    |--------------------------------------------------------------------------
    */

    public function scopeKept($query)
    {
        return $query->where(
            'status',
            'kept'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Escalated
    |--------------------------------------------------------------------------
    */

    public function scopeEscalated($query)
    {
        return $query->where(
            'is_escalated',
            1
        );
    }
}