<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewMenuItemVariant extends RestaurantNewBaseModel
{
    protected $table = 'rn_menu_item_variants';

    protected $casts = [
        'price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(RestaurantNewMenuItem::class, 'menu_item_id');
    }
}
