<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewShareHolding extends Model
{
    use SoftDeletes;

    protected $table = 'mn_share_holdings';
    protected $guarded = ['id'];
    protected $casts = ['shares'=>'decimal:4','share_value'=>'decimal:4'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }


    public function member()
    {
        return $this->belongsTo(MembershipNewMember::class, 'member_id');
    }

}
