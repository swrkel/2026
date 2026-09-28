<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewRecipeLine extends Model
{
    protected $table = 'restaurant_new_recipe_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'line_cost' => 'decimal:4',
        'is_optional' => 'boolean',
    ];

    public function ingredient()
    {
        return $this->belongsTo(RestaurantNewIngredient::class, 'ingredient_id');
    }
}
