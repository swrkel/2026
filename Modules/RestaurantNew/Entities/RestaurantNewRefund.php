<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantNewRefund extends RestaurantNewBaseModel
{
    protected $table = 'restaurant_new_refunds';

    protected $fillable = [
        'business_id', 'location_id', 'restaurant_new_bill_id', 'refund_no', 'refund_date',
        'refund_method', 'amount', 'reason', 'status', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'refund_date' => 'datetime',
        'amount' => 'decimal:4',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(RestaurantNewBill::class, 'restaurant_new_bill_id');
    }
}
