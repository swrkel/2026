<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewDividendPayment extends Model
{
    use SoftDeletes;

    protected $table = 'mn_dividend_payments';
    protected $guarded = ['id'];
    protected $casts = ['shares'=>'decimal:4','amount'=>'decimal:4','is_paid'=>'boolean','paid_at'=>'datetime'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }


    public function member()
    {
        return $this->belongsTo(MembershipNewMember::class, 'member_id');
    }

}
