<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

use Modules\Loan\Models\LoanCustomer;
use Modules\Loan\Models\LoanGuarantor;
use Modules\Loan\Models\LoanCollateral;
use Modules\Loan\Models\LoanDocument;
use Modules\Loan\Models\LoanRecoveryAssignment;

class LoanApplication extends Model
{
    protected $table = 'loan_applications';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Customer Relationship
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
    | Loan Product Relationship
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
    | Guarantors
    |--------------------------------------------------------------------------
    */

    public function guarantors()
    {
        return $this->hasMany(
            LoanGuarantor::class,
            'loan_application_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Collaterals
    |--------------------------------------------------------------------------
    */

    public function collaterals()
    {
        return $this->hasMany(
            LoanCollateral::class,
            'loan_application_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */

    public function documents()
    {
        return $this->hasMany(
            LoanDocument::class,
            'loan_application_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Active Recovery Assignment
    |--------------------------------------------------------------------------
    */

    public function activeRecoveryAssignment()
    {
        return $this->hasOne(
            LoanRecoveryAssignment::class,
            'loan_application_id'
        )->where(
            'status',
            'active'
        );
    }
}
