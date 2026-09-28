<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewPlan extends Model
{
    use SoftDeletes;

    protected $table = 'mn_plans';

    protected $guarded = ['id'];

    protected $casts = ['price' => 'decimal:4', 'duration_days' => 'integer', 'is_active' => 'boolean'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
