<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewTableOperation extends Model
{
    protected $table = 'rn_table_operations';
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(RestaurantNewSaleOrder::class, 'order_id');
    }
}
