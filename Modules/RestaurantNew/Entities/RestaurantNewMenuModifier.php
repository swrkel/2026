<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewMenuModifier extends RestaurantNewBaseModel
{
    protected $table = 'rn_menu_modifiers';

    protected $casts = [
        'price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(RestaurantNewMenuItem::class, 'menu_item_id');
    }
}
