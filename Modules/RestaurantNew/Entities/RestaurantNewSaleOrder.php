<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewSaleOrder extends Model
{
    protected $table = 'rn_sale_orders';
    protected $guarded = ['id'];

    public function lines()
    {
        return $this->hasMany(RestaurantNewSaleOrderLine::class, 'order_id');
    }
}
