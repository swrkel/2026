<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewMenuItem extends RestaurantNewBaseModel
{
    protected $table = 'rn_menu_items';

    protected $casts = [
        'price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'tax_percent' => 'decimal:4',
        'preparation_time_minutes' => 'integer',
        'is_active' => 'boolean',
        'is_modifier_required' => 'boolean',
        'allow_discount' => 'boolean',
        'track_recipe_stock' => 'boolean',
        'available_for_dine_in' => 'boolean',
        'available_for_takeaway' => 'boolean',
        'available_for_delivery' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RestaurantNewMenuCategory::class, 'menu_category_id');
    }

    public function kitchenSection()
    {
        return $this->belongsTo(RestaurantNewKitchenSection::class, 'kitchen_section_id');
    }

    public function variants()
    {
        return $this->hasMany(RestaurantNewMenuItemVariant::class, 'menu_item_id');
    }

    public function modifiers()
    {
        return $this->hasMany(RestaurantNewMenuModifier::class, 'menu_item_id');
    }

    public function recipes()
    {
        return $this->hasMany(RestaurantNewMenuRecipe::class, 'menu_item_id');
    }
}
