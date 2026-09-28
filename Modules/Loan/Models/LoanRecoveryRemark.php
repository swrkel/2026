<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRecoveryRemark extends Model
{
    protected $fillable = [

        'loan_application_id',

        'remark',

        'next_followup_date',

        'promise_to_pay',

        'promised_amount',

        'promised_payment_date',

        'created_by'
    ];
}