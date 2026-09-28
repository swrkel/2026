<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCustomerOrderTracking extends Model
{
    protected $table = 'restaurant_new_customer_order_tracking';
    protected $guarded = ['id'];
    protected $casts = [
        'public_payload' => 'array',
        'meta' => 'array',
        'acknowledged_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_status_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
