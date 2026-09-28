<?php

namespace Modules\Loan\Entities;

use Illuminate\Database\Eloquent\Model;

class LoanChargeOption extends Model
{
    protected $fillable = [];
    public $table = "loan_charge_options";
    public $timestamps = false;

    public function getLocalizedNameAttribute()
    {
        if ($this->id == 1) {
            return trans_choice('loan::general.flat', 1);
        }
        if ($this->id == 2) {
            return trans_choice('loan::general.principal_due_on_installment', 1);
        }
        if ($this->id == 3) {
            return trans_choice('loan::general.principal_interest_due_on_installment', 1);
        }
        if ($this->id == 4) {
            return trans_choice('loan::general.interest_due_on_installment', 1);
        }
        if ($this->id == 5) {
            return trans_choice('loan::general.total_outstanding_loan_principal', 1);
        }
        if ($this->id == 6) {
            return trans_choice('loan::general.percentage_of_original_loan_principal_per_installment', 1);
        }
        if ($this->id == 7) {
            return trans_choice('loan::general.original_loan_principal', 1);
        }
        return $this->name;
    }
}
