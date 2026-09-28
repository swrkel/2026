<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewIngredient extends Model
{
    protected $table = 'restaurant_new_ingredients';

    protected $guarded = ['id'];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RestaurantNewIngredientCategory::class, 'category_id');
    }
}
