<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantNewKitchenQueue extends Model
{
    use SoftDeletes;

    protected $table = 'resnew_kitchen_queues';

    protected $fillable = [
        'business_id','location_id','kitchen_section_id','order_id','order_item_id','kot_id',
        'queue_no','priority','status','received_at','started_at','ready_at','served_at',
        'estimated_minutes','actual_minutes','notes','created_by','updated_by'
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
    ];
}
