<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPenalty extends Model
{
    protected $table = 'loan_penalties';

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Tenant
        |--------------------------------------------------------------------------
        */

        'business_id',

        /*
        |--------------------------------------------------------------------------
        | Penalty Details
        |--------------------------------------------------------------------------
        */

        'name',

        'code',

        'description',

        'penalty_type',

        'calculation_method',

        'default_value',

        'minimum_value',

        'maximum_value',

        /*
        |--------------------------------------------------------------------------
        | Penalty Application Rules
        |--------------------------------------------------------------------------
        */

        'grace_period_days',

        'apply_after_days',

        'maximum_penalty_limit',

        'daily_accrual',

        'compound_penalty',

        /*
        |--------------------------------------------------------------------------
        | Governance Controls
        |--------------------------------------------------------------------------
        */

        'is_waivable',

        'requires_approval',

        'auto_apply',

        'priority_level',

        /*
        |--------------------------------------------------------------------------
        | Recovery Optimization
        |--------------------------------------------------------------------------
        */

        'recovery_stage',

        'escalation_trigger',

        'legal_recovery_applicable',

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
    | Penalty Type Constants
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
            'penalty_id'
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

    public function getFormattedPenaltyAttribute()
    {
        if (
            $this->penalty_type ==
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

    public function getRiskBadgeAttribute()
    {
        switch ($this->priority_level) {

            case 'high':
                return 'danger';

            case 'medium':
                return 'warning';

            default:
                return 'success';
        }
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