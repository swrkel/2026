<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewPurchaseOrder extends Model
{
    protected $table = 'restaurant_new_purchase_orders';
    protected $guarded = ['id'];
}
