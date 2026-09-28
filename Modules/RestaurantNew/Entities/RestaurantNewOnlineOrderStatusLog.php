<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewOnlineOrderStatusLog extends Model
{
    protected $table = 'restaurant_new_online_order_status_logs';
    protected $guarded = ['id'];
    protected $casts = [];
}
