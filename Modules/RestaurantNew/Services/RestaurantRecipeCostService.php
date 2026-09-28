<?php

namespace Modules\RestaurantNew\Services;

use Modules\RestaurantNew\Entities\RestaurantNewRecipe;

class RestaurantRecipeCostService
{
    public function recalculate(int $recipeId): float
    {
        $recipe = RestaurantNewRecipe::with('lines.ingredient')->findOrFail($recipeId);
        $total = 0;

        foreach ($recipe->lines as $line) {
            $unitCost = (float) optional($line->ingredient)->purchase_price;
            $line->unit_cost = $unitCost;
            $line->line_cost = $unitCost * (float) $line->quantity;
            $line->save();
            $total += (float) $line->line_cost;
        }

        $recipe->estimated_cost = $total;
        $recipe->save();

        return $total;
    }
}
