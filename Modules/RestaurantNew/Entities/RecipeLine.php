<?php
namespace Modules\RestaurantNew\Entities;

class RecipeLine extends RestnewModel
{
    protected $table = 'restnew_recipe_lines';

public function ingredient() { return $this->belongsTo(Ingredient::class, 'ingredient_id'); }
}
