<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCategory extends Model
{
    protected $table = 'loan_categories';

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Tenant
        |--------------------------------------------------------------------------
        */

        'business_id',

        /*
        |--------------------------------------------------------------------------
        | Category Details
        |--------------------------------------------------------------------------
        */

        'name',

        'code',

        'description',

        'status',

        /*
        |--------------------------------------------------------------------------
        | Governance
        |--------------------------------------------------------------------------
        */

        'risk_level',

        'priority_level',

        'recovery_strategy',

        /*
        |--------------------------------------------------------------------------
        | Workforce Optimization
        |--------------------------------------------------------------------------
        */

        'auto_assign_collectors',

        'enable_escalations',

        'allow_settlements',

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

    public function products()
    {
        return $this->hasMany(
            \Modules\Loan\Models\LoanProduct::class,
            'category_id'
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

    public function getRiskBadgeAttribute()
    {
        switch ($this->risk_level) {

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