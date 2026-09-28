<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewComboMeal extends Model
{
    protected $table = 'restaurant_new_combo_meals';
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(RestaurantNewComboMealItem::class, 'combo_meal_id');
    }
}
