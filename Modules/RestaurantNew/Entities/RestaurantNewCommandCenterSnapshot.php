<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCommandCenterSnapshot extends Model
{
    protected $table = 'restaurant_new_command_center_snapshots';
    protected $guarded = ['id'];
    protected $casts = [
        'meta' => 'array',
        'is_read' => 'boolean',
    ];
}
