<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewMember extends Model
{
    use SoftDeletes;

    protected $table = 'mn_members';
    protected $guarded = ['id'];

    protected $casts = [
        'joined_on' => 'date',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'region_id' => 'integer',
        'membership_type_id' => 'integer',
        'no_of_shares' => 'decimal:4',
        'total_share_value' => 'decimal:4',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }


    public function region()
    {
        return $this->belongsTo(MembershipNewRegion::class, 'region_id');
    }

    public function membershipType()
    {
        return $this->belongsTo(MembershipNewSettingOption::class, 'membership_type_id');
    }

    public function activeCard()
    {
        return $this->hasOne(MembershipNewIdentityCard::class, 'member_id')->where('is_active', 1)->latestOfMany();
    }

    public function customerMaps()
    {
        return $this->hasMany(MembershipNewCustomerMap::class, 'member_id');
    }

    public function shareHolding()
    {
        return $this->hasOne(MembershipNewShareHolding::class, 'member_id')->latestOfMany();
    }
}
