<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewMemberBusinessMap extends Model
{
    use SoftDeletes;

    protected $table = 'mn_member_business_maps';
    protected $guarded = ['id'];

    protected $casts = [
        'points_enabled' => 'boolean',
        'dividend_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function centralMember()
    {
        return $this->belongsTo(MembershipNewCentralMember::class, 'central_member_id');
    }
}
