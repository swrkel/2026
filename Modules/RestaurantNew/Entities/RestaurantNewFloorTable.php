<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewFloorTable extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['position_payload'=>'array'];
}
