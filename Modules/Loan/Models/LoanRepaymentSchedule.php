<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepaymentSchedule extends Model
{
    protected $table = 'loan_repayment_schedules';

    protected $guarded = [];

    public function loan()
    {
        return $this->belongsTo(
            Loan::class,
            'loan_id'
        );
    }
}