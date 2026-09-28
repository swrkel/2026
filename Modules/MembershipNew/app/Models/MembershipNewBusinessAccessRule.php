<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewBusinessAccessRule extends Model
{
    use SoftDeletes;

    protected $table = 'mn_business_access_rules';
    protected $guarded = ['id'];

    protected $casts = [
        'can_view_central_profile' => 'boolean',
        'can_view_other_business_history' => 'boolean',
        'can_redeem_cross_business_points' => 'boolean',
        'can_issue_card' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
