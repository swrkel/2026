<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewIdentityCard extends Model
{
    use SoftDeletes;

    protected $table = 'mn_identity_cards';
    protected $guarded = ['id'];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
        'is_active' => 'boolean',
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
