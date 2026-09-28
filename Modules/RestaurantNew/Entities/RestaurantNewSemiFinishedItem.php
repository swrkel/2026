<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewSemiFinishedItem extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_active'=>'boolean'];
}
