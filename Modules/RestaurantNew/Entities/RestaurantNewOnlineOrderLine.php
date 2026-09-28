<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewOnlineOrderLine extends Model
{
    protected $table = 'restaurant_new_online_order_lines';
    protected $guarded = ['id'];
    protected $casts = ['modifiers' => 'array',];
}
