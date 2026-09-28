<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewHappyHour extends Model
{
    protected $table = 'restaurant_new_happy_hours';
    protected $guarded = ['id'];
}
