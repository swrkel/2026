<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewOutletTransactionQueue extends Model
{
    use SoftDeletes;

    protected $table = 'mn_outlet_transaction_queue';
    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'datetime',
        'purchase_amount' => 'decimal:4',
        'earn_points' => 'decimal:4',
        'redeem_points' => 'decimal:4',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
