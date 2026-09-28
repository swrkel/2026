<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewKitchenPerformanceLog extends Model
{
    protected $table = 'resnew_kitchen_performance_logs';

    protected $fillable = [
        'business_id','location_id','kitchen_section_id','queue_id','order_id','order_item_id',
        'staff_id','metric_date','target_minutes','actual_minutes','delay_minutes','status','remarks'
    ];
}
