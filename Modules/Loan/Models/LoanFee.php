<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanFee extends Model
{
    protected $table = 'loan_fees';

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Tenant
        |--------------------------------------------------------------------------
        */

        'business_id',

        /*
        |--------------------------------------------------------------------------
        | Fee Details
        |--------------------------------------------------------------------------
        */

        'name',

        'code',

        'description',

        'fee_type',

        'calculation_method',

        'apply_when',

        'default_value',

        'minimum_value',

        'maximum_value',

        /*
        |--------------------------------------------------------------------------
        | Governance
        |--------------------------------------------------------------------------
        */

        'is_mandatory',

        'is_editable',

        'is_taxable',

        'priority_order',

        /*
        |--------------------------------------------------------------------------
        | Recovery Intelligence
        |--------------------------------------------------------------------------
        */

        'recovery_applicable',

        'allow_fee_waiver',

        'auto_apply',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',

        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        'created_by',

        'updated_by',

    ];

    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    /*
    |--------------------------------------------------------------------------
    | Fee Type Constants
    |--------------------------------------------------------------------------
    */

    const TYPE_FIXED = 'fixed';

    const TYPE_PERCENTAGE = 'percentage';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }

    public function updater()
    {
        return $this->belongsTo(
            \App\User::class,
            'updated_by'
        );
    }

    public function loanProducts()
    {
        return $this->hasMany(
            \Modules\Loan\Models\LoanProduct::class,
            'fee_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'status',
            self::STATUS_ACTIVE
        );
    }

    public function scopeForBusiness(
        $query,
        $business_id
    ) {
        return $query->where(
            'business_id',
            $business_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedValueAttribute()
    {
        if (
            $this->fee_type ==
            self::TYPE_PERCENTAGE
        ) {

            return number_format(
                $this->default_value,
                2
            ) . '%';
        }

        return number_format(
            $this->default_value,
            2
        );
    }

    public function getStatusBadgeAttribute()
    {
        return $this->status ==
            self::STATUS_ACTIVE
            ? 'success'
            : 'danger';
    }

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            if (empty($model->status)) {

                $model->status =
                    self::STATUS_ACTIVE;
            }

            if (auth()->check()) {

                $model->created_by =
                    auth()->id();
            }
        });

        static::updating(function ($model) {

            if (auth()->check()) {

                $model->updated_by =
                    auth()->id();
            }
        });
    }
}