<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPromiseToPayLog extends Model
{
    protected $table = 'loan_promise_to_pay_logs';

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
}