<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    protected $table = 'loan_products';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Tenant
        |--------------------------------------------------------------------------
        */

        'business_id',

        /*
        |--------------------------------------------------------------------------
        | Basic Details
        |--------------------------------------------------------------------------
        */

        'name',

        'code',

        'description',

        'status',

        'notes',

        /*
        |--------------------------------------------------------------------------
        | Loan Limits
        |--------------------------------------------------------------------------
        */

        'minimum_amount',

        'maximum_amount',

        'minimum_loan_term',

        'maximum_loan_term',

        'duration',

        'duration_type',

        /*
        |--------------------------------------------------------------------------
        | Interest Configuration
        |--------------------------------------------------------------------------
        */

        'interest_rate',

        'minimum_interest_rate',

        'maximum_interest_rate',

        'default_interest_rate',

        'interest_method',

        'interest_frequency',

        'calculation_method',

        'compounding_period',

        'grace_period',

        'grace_period_cycle',

        'processing_fee',

        'late_payment_charge',

        /*
        |--------------------------------------------------------------------------
        | Repayment
        |--------------------------------------------------------------------------
        */

        'repayment_frequency',

        'number_of_installments',

        'allow_partial_payments',

        'allow_early_settlement',

        'auto_generate_schedule',

        /*
        |--------------------------------------------------------------------------
        | Relations
        |--------------------------------------------------------------------------
        */

        'category_id',

        'currency_id',

        'location_id',

        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'minimum_amount' => 'decimal:2',

        'maximum_amount' => 'decimal:2',

        'interest_rate' => 'decimal:2',

        'minimum_interest_rate' => 'decimal:2',

        'maximum_interest_rate' => 'decimal:2',

        'default_interest_rate' => 'decimal:2',

        'processing_fee' => 'decimal:2',

        'late_payment_charge' => 'decimal:2',

        'allow_partial_payments' => 'boolean',

        'allow_early_settlement' => 'boolean',

        'auto_generate_schedule' => 'boolean',
    ];

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

    /*
    |--------------------------------------------------------------------------
    | Category
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(
            LoanCategory::class,
            'category_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    public function currency()
    {
        return $this->belongsTo(
            \App\Currency::class,
            'currency_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Locations
    |--------------------------------------------------------------------------
    */

    public function locations()
    {
        return $this->belongsToMany(
            \App\BusinessLocation::class,
            'loan_product_locations',
            'loan_product_id',
            'location_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Fees
    |--------------------------------------------------------------------------
    */

    public function fees()
    {
        return $this->belongsToMany(
            LoanFee::class,
            'loan_product_fees',
            'loan_product_id',
            'loan_fee_id'
        )->withPivot(
            'fee_type',
            'fee_value',
            'calculation_based_on',
            'applied_when'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Penalties
    |--------------------------------------------------------------------------
    */

    public function penalties()
    {
        return $this->belongsToMany(
            LoanPenalty::class,
            'loan_product_penalties',
            'loan_product_id',
            'loan_penalty_id'
        )->withPivot(
            'penalty_type',
            'penalty_value',
            'grace_period',
            'grace_period_cycle'
        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Business
    |--------------------------------------------------------------------------
    */

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where(
            'business_id',
            $business_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Active
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'status',
            'active'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

    public function getFormattedInterestRateAttribute()
    {
        return number_format(
            $this->interest_rate,
            2
        ) . '%';
    }
}