<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRecoverySettlement extends Model
{
    protected $table =
        'loan_recovery_settlements';

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
    | Approved By
    |--------------------------------------------------------------------------
    */

    public function approvedBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'approved_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Created By
    |--------------------------------------------------------------------------
    */

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Approved Settlements
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where(
            'settlement_status',
            'approved'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Pending Settlements
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where(
            'settlement_status',
            'pending'
        );
    }
}