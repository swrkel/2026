<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCollectionAction extends Model
{
    protected $table =
        'loan_collection_actions';

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
    | Recovery Officer
    |--------------------------------------------------------------------------
    */

    public function officer()
    {
        return $this->belongsTo(
            \App\User::class,
            'recovery_officer_id'
        );
    }
}