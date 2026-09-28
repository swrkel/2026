<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewIngredientStock extends Model
{
    protected $table = 'restaurant_new_ingredient_stocks';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'average_cost' => 'decimal:4',
        'stock_value' => 'decimal:4',
    ];

    public function ingredient()
    {
        return $this->belongsTo(RestaurantNewIngredient::class, 'ingredient_id');
    }
}
