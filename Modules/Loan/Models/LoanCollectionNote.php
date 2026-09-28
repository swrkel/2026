<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCollectionNote extends Model
{
    protected $table =
        'loan_collection_notes';

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
    | Repayment Schedule
    |--------------------------------------------------------------------------
    */

    public function schedule()
    {
        return $this->belongsTo(
            LoanRepaymentSchedule::class,
            'schedule_id'
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
    | Collection Officer
    |--------------------------------------------------------------------------
    */

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }
}