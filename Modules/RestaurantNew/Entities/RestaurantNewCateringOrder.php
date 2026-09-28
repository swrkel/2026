<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCateringOrder extends Model
{
    protected $table = 'restaurant_new_catering_orders';
    protected $guarded = ['id'];
}
