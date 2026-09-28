<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantNewBill extends RestaurantNewBaseModel
{
    protected $table = 'restaurant_new_bills';

    protected $fillable = [
        'business_id', 'location_id', 'restaurant_new_order_id', 'bill_no', 'bill_date',
        'customer_id', 'cashier_id', 'waiter_id', 'subtotal', 'discount_type',
        'discount_amount', 'tax_amount', 'service_charge_amount', 'round_off_amount',
        'grand_total', 'paid_total', 'balance_due', 'change_amount', 'payment_status',
        'bill_status', 'notes', 'void_reason', 'voided_by', 'voided_at', 'created_by',
    ];

    protected $casts = [
        'bill_date' => 'datetime',
        'voided_at' => 'datetime',
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'service_charge_amount' => 'decimal:4',
        'round_off_amount' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'paid_total' => 'decimal:4',
        'balance_due' => 'decimal:4',
        'change_amount' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantNewOrder::class, 'restaurant_new_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RestaurantNewBillLine::class, 'restaurant_new_bill_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RestaurantNewBillPayment::class, 'restaurant_new_bill_id');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForLocation($query, ?int $locationId)
    {
        return $locationId ? $query->where('location_id', $locationId) : $query;
    }
}
