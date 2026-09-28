<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewMenuProfitability extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['period_date'=>'date'];
}
