<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewDividendBatch extends Model
{
    use SoftDeletes;

    protected $table = 'mn_dividend_batches';
    protected $guarded = ['id'];
    protected $casts = ['dividend_date'=>'date','total_dividend_amount'=>'decimal:4','dividend_per_share'=>'decimal:6','is_posted'=>'boolean','posted_at'=>'datetime'];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }


    public function payments()
    {
        return $this->hasMany(MembershipNewDividendPayment::class, 'batch_id');
    }

}
