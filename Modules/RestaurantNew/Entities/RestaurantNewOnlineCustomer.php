<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewOnlineCustomer extends Model
{
    protected $table = 'restaurant_new_online_customers';
    protected $guarded = ['id'];
    protected $casts = [];
}
