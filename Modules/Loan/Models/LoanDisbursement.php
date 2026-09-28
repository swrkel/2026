<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDisbursement extends Model
{
    protected $table = 'loan_disbursements';
    protected $guarded = [];

    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}
