<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantNewOrder extends Model
{
    use SoftDeletes;

    protected $table = 'restaurant_new_orders';

    protected $fillable = [
        'business_id', 'location_id', 'dining_area_id', 'restaurant_table_id', 'customer_id',
        'order_no', 'order_type', 'order_status', 'kot_status', 'payment_status',
        'guest_count', 'waiter_id', 'cashier_id', 'subtotal', 'discount_type', 'discount_amount',
        'service_charge_amount', 'tax_amount', 'delivery_charge', 'round_off', 'grand_total',
        'paid_amount', 'balance_amount', 'order_note', 'opened_at', 'completed_at', 'cancelled_at',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'subtotal' => 'decimal:4', 'discount_amount' => 'decimal:4',
        'service_charge_amount' => 'decimal:4', 'tax_amount' => 'decimal:4',
        'delivery_charge' => 'decimal:4', 'round_off' => 'decimal:4', 'grand_total' => 'decimal:4',
        'paid_amount' => 'decimal:4', 'balance_amount' => 'decimal:4',
        'opened_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(RestaurantNewOrderLine::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(RestaurantNewOrderPayment::class, 'order_id');
    }
}
