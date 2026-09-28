<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewBusinessCustomerHistory extends Model
{
    use SoftDeletes;

    protected $table = 'mn_business_customer_histories';
    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'datetime',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'balance' => 'decimal:4',
        'meta' => 'array',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function businessMap()
    {
        return $this->belongsTo(MembershipNewMemberBusinessMap::class, 'member_business_map_id');
    }
}
