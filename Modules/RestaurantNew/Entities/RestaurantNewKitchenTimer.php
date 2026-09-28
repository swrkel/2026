<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewKitchenTimer extends Model
{
    protected $table = 'resnew_kitchen_timers';

    protected $fillable = [
        'business_id','location_id','queue_id','order_id','order_item_id','timer_type',
        'started_at','paused_at','ended_at','total_seconds','status','created_by'
    ];

    protected $casts = ['started_at' => 'datetime','paused_at' => 'datetime','ended_at' => 'datetime'];
}
