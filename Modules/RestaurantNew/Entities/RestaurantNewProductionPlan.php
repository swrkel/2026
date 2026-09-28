<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewProductionPlan extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['plan_date'=>'date','approved_at'=>'datetime'];
}
