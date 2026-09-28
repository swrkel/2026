<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewCustomerMap extends Model
{
    use SoftDeletes;

    protected $table = 'mn_customer_maps';
    protected $guarded = ['id'];

    protected $casts = [
        'member_snapshot' => 'array',
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function member()
    {
        return $this->belongsTo(MembershipNewMember::class, 'member_id');
    }
}
