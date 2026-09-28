<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewTableUtilization extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['utilization_date'=>'date','hourly_usage'=>'array'];
}
