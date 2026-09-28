<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewPointTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'mn_point_transactions';
    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'datetime',
        'points' => 'decimal:4',
        'purchase_amount' => 'decimal:4',
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
