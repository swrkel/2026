<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSetting extends Model
{
    protected $table = 'loan_settings';

    protected $fillable = [

        'business_id',

        'default_currency_id',

        'default_interest_method',

        'default_installment_frequency',

        'created_by'

    ];
}