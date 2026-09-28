<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCustomerVisit extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['ordered_items_summary'=>'array','visited_at'=>'datetime'];
}
