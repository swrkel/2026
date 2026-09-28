<?php

namespace Modules\Loan\Entities;

use Illuminate\Database\Eloquent\Model;

class LoanChargeType extends Model
{
    protected $fillable = [];
    public $table = "loan_charge_types";
    public $timestamps = false;

    public function getLocalizedNameAttribute()
    {
        if ($this->id == 1) {
            return trans_choice('loan::general.disbursement', 1);
        }
        if ($this->id == 2) {
            return trans_choice('loan::general.specified_due_date', 1);
        }
        if ($this->id == 3) {
            return trans_choice('loan::general.installment', 1) . ' ' . trans_choice('loan::general.fee', 2);
        }
        if ($this->id == 4) {
            return trans_choice('loan::general.overdue', 1) . ' ' . trans_choice('loan::general.installment', 1) . ' ' . trans_choice('loan::general.fee', 1);
        }
        if ($this->id == 5) {
            return trans_choice('loan::general.disbursement_paid_with_repayment', 1);
        }
        if ($this->id == 6) {
            return trans_choice('loan::general.loan_rescheduling_fee', 1);
        }
        if ($this->id == 7) {
            return trans_choice('loan::general.overdue_on_loan_maturity', 1);
        }
        if ($this->id == 8) {
            return trans_choice('loan::general.last_installment_fee', 1);
        }
        return $this->name;
    }
}
