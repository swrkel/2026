<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewMenuItemAllergen extends Model
{
    protected $table = 'restaurant_new_menu_item_allergens';
    protected $guarded = ['id'];
}
