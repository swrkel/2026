<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewMenuItemDietaryTag extends Model
{
    protected $table = 'restaurant_new_menu_item_dietary_tags';
    protected $guarded = ['id'];
}
