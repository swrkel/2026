<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewWaitlist extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['seated_at'=>'datetime'];
}
