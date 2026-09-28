<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewMenuCategory extends RestaurantNewBaseModel
{
    protected $table = 'rn_menu_categories';

    protected $casts = [
        'is_active' => 'boolean',
        'available_for_dine_in' => 'boolean',
        'available_for_takeaway' => 'boolean',
        'available_for_delivery' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(RestaurantNewMenuItem::class, 'menu_category_id');
    }
}
