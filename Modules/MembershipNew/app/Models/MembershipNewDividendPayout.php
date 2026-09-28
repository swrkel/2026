<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewDividendPayout extends Model
{
    use SoftDeletes;

    protected $table = 'mn_dividend_payouts';
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'datetime',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
