<?php
namespace Modules\RestaurantNew\Entities;

class MenuItem extends RestnewModel
{
    protected $table = 'restnew_menu_items';
    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'is_takeaway' => 'boolean',
        'is_delivery' => 'boolean',
        'is_dine_in' => 'boolean',
        'metadata' => 'array',
    ];

public function category() { return $this->belongsTo(Category::class, 'category_id'); }
public function station() { return $this->belongsTo(KitchenStation::class, 'station_id'); }
public function recipe() { return $this->hasOne(Recipe::class, 'menu_item_id'); }
public function modifierGroups() { return $this->belongsToMany(ModifierGroup::class, 'restnew_menu_item_modifier_groups', 'menu_item_id', 'modifier_group_id')->withPivot(['business_id','sort_order'])->withTimestamps(); }
}
