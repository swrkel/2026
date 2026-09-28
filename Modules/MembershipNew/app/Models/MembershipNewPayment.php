<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewPayment extends Model
{
    use SoftDeletes;

    protected $table = 'mn_payments';

    protected $guarded = ['id'];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function member()
    {
        return $this->belongsTo(MembershipNewMember::class, 'member_id');
    }

    public function plan()
    {
        return $this->belongsTo(MembershipNewPlan::class, 'plan_id');
    }
}
