<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewIngredientCategory extends Model
{
    protected $table = 'restaurant_new_ingredient_categories';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
