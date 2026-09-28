<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewMenuRecipe extends RestaurantNewBaseModel
{
    protected $table = 'rn_menu_recipes';

    protected $casts = [
        'quantity' => 'decimal:4',
        'wastage_percent' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(RestaurantNewMenuItem::class, 'menu_item_id');
    }
}
