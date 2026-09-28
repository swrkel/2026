<?php

namespace Modules\Membership\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class MembershipMemberRenewal extends Model
{
    protected $fillable = [
        'business_id',
        'membership_member_id',
        'renewal_period',
        'renewal_cycles',
        'next_renewal_date',
        'renewal_amount',
        'renewed_by',
    ];

    public function member()
    {
        return $this->belongsTo(MembershipMember::class, 'membership_member_id');
    }

    public function renewedBy()
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
