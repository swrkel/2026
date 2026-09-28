<?php
namespace Modules\RestaurantNew\Entities;

class Recipe extends RestnewModel
{
    protected $table = 'restnew_recipes';
    protected $casts = [
        'is_active' => 'boolean',
    ];

public function item() { return $this->belongsTo(MenuItem::class, 'menu_item_id'); }
public function lines() { return $this->hasMany(RecipeLine::class, 'recipe_id'); }
}
