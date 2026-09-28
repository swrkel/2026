<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewPointRule extends Model
{
    use SoftDeletes;

    protected $table = 'mn_point_rules';
    protected $guarded = ['id'];
    protected $casts = ['amount_step'=>'decimal:4','points_per_amount'=>'decimal:4','max_points_per_invoice'=>'decimal:4','is_active'=>'boolean'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }


}
