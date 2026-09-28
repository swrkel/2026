<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewRecipe extends Model
{
    protected $table = 'restaurant_new_recipes';

    protected $guarded = ['id'];

    protected $casts = [
        'yield_qty' => 'decimal:4',
        'estimated_cost' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(RestaurantNewRecipeLine::class, 'recipe_id');
    }
}
