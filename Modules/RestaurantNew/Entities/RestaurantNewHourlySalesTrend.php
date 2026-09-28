<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewHourlySalesTrend extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['trend_date'=>'date','order_type_breakdown'=>'array'];
}
