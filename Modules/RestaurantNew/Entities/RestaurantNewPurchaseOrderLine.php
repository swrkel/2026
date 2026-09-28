<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewPurchaseOrderLine extends Model
{
    protected $table = 'restaurant_new_purchase_order_lines';
    protected $guarded = ['id'];
}
