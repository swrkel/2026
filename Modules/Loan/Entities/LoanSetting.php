<?php

namespace Modules\Loan\Entities;

use Illuminate\Database\Eloquent\Model;

class LoanSetting extends Model
{
    protected $table = 'loan_settings';

    protected $fillable = [
        'business_id',
        'setting_key',
        'setting_value',
        'created_by',
        'updated_by',
    ];
}
