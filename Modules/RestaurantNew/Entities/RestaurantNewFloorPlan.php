<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewFloorPlan extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['layout_payload'=>'array','is_active'=>'boolean'];
}
