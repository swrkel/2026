<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewAllergen extends Model
{
    protected $table = 'restaurant_new_allergens';
    protected $guarded = ['id'];
}
