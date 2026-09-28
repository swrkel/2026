<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantNewBillPayment extends RestaurantNewBaseModel
{
    protected $table = 'restaurant_new_bill_payments';

    protected $fillable = [
        'business_id', 'location_id', 'restaurant_new_bill_id', 'payment_date', 'payment_method',
        'account_id', 'reference_no', 'card_type', 'card_last_four', 'amount', 'payment_status',
        'note', 'created_by', 'voided_by', 'voided_at',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'voided_at' => 'datetime',
        'amount' => 'decimal:4',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(RestaurantNewBill::class, 'restaurant_new_bill_id');
    }
}
