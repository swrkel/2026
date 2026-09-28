<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewMultiPayment extends Model
{
    protected $table = 'rn_multi_payments';
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(RestaurantNewSaleOrder::class, 'order_id');
    }
}
