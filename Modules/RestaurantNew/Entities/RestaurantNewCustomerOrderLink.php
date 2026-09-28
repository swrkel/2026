<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCustomerOrderLink extends Model
{
    protected $table = 'restaurant_new_customer_order_links';

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];
}
