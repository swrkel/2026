<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewOnlineOrder extends Model
{
    protected $table = 'restaurant_new_online_orders';
    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array',];

    public function lines()
    {
        return $this->hasMany(RestaurantNewOnlineOrderLine::class, 'online_order_id');
    }

    public function customer()
    {
        return $this->belongsTo(RestaurantNewOnlineCustomer::class, 'online_customer_id');
    }
}
