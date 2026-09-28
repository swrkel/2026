<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewSaleOrderLine extends Model
{
    protected $table = 'rn_sale_order_lines';
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(RestaurantNewSaleOrder::class, 'order_id');
    }
}
