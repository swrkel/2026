<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

use Modules\Loan\Models\LoanCustomer;
use Modules\Loan\Models\LoanRepaymentSchedule;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanCollectionNote;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanRecoverySettlement;

class Loan extends Model
{
    protected $table = 'loans';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

public function customer()
{
    return $this->belongsTo(
        LoanCustomer::class,
        'loan_customer_id'
    );
}

    /*
    |--------------------------------------------------------------------------
    | Loan Product
    |--------------------------------------------------------------------------
    */

    public function loanProduct()
    {
        return $this->belongsTo(
            LoanProduct::class,
            'loan_product_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Loan Application
    |--------------------------------------------------------------------------
    */

    public function application()
    {
        return $this->belongsTo(
            LoanApplication::class,
            'loan_application_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Repayment Schedules
    |--------------------------------------------------------------------------
    */

    public function repaymentSchedules()
    {
        return $this->hasMany(
            LoanRepaymentSchedule::class,
            'loan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recoveries
    |--------------------------------------------------------------------------
    */

    public function recoveries()
    {
        return $this->hasMany(
            LoanRecovery::class,
            'loan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Notes
    |--------------------------------------------------------------------------
    */

    public function collectionNotes()
    {
        return $this->hasMany(
            LoanCollectionNote::class,
            'loan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recovery Escalations
    |--------------------------------------------------------------------------
    */

    public function recoveryEscalations()
    {
        return $this->hasMany(
            LoanRecoveryEscalation::class,
            'loan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recovery Settlements
    |--------------------------------------------------------------------------
    */

    public function recoverySettlements()
    {
        return $this->hasMany(
            LoanRecoverySettlement::class,
            'loan_id'
        );
    }
}