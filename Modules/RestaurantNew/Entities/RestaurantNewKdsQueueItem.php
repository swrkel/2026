<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewKdsQueueItem extends Model
{
    protected $table = 'restaurant_new_kds_queue_items';
    protected $guarded = ['id'];
    protected $casts = [
        'visible_order_types' => 'array',
        'status_filter' => 'array',
        'meta' => 'array',
        'payload' => 'array',
        'sound_enabled' => 'boolean',
        'is_active' => 'boolean',
        'is_read' => 'boolean',
        'received_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'collected_at' => 'datetime',
        'served_at' => 'datetime',
        'changed_at' => 'datetime',
    ];
}
