<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewOrderPayment extends Model
{
    protected $table = 'restaurant_new_order_payments';

    protected $fillable = [
        'business_id', 'location_id', 'order_id', 'payment_method', 'payment_account_id',
        'amount', 'reference_no', 'paid_on', 'payment_note', 'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:4', 'paid_on' => 'datetime',
    ];
}
