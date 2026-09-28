<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewAiForecast extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['forecast_date'=>'date','forecast_payload'=>'array'];
}
