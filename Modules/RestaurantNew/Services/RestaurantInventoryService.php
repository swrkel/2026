<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewIngredientStock;
use Modules\RestaurantNew\Entities\RestaurantNewStockMovement;
use Modules\RestaurantNew\Entities\RestaurantNewWastage;
use Modules\RestaurantNew\Entities\RestaurantNewRecipe;

class RestaurantInventoryService
{
    public function receiveIngredient(int $businessId, int $locationId, int $ingredientId, float $quantity, float $unitCost, ?string $notes = null): RestaurantNewIngredientStock
    {
        return DB::transaction(function () use ($businessId, $locationId, $ingredientId, $quantity, $unitCost, $notes) {
            $stock = $this->stockRow($businessId, $locationId, $ingredientId);
            $oldQty = (float) $stock->quantity;
            $oldValue = (float) $stock->stock_value;
            $newValue = $oldValue + ($quantity * $unitCost);
            $newQty = $oldQty + $quantity;

            $stock->quantity = $newQty;
            $stock->average_cost = $newQty > 0 ? $newValue / $newQty : 0;
            $stock->stock_value = $newValue;
            $stock->save();

            $this->movement($businessId, $locationId, $ingredientId, 'receive', $quantity, 0, $stock->quantity, $unitCost, $notes);

            return $stock;
        });
    }

    public function consumeRecipe(int $businessId, int $locationId, int $recipeId, float $portionQty, ?string $referenceType = null, ?int $referenceId = null): void
    {
        DB::transaction(function () use ($businessId, $locationId, $recipeId, $portionQty, $referenceType, $referenceId) {
            $recipe = RestaurantNewRecipe::with('lines')->where('business_id', $businessId)->findOrFail($recipeId);
            $yield = max((float) $recipe->yield_qty, 1);

            foreach ($recipe->lines as $line) {
                if ($line->is_optional) {
                    continue;
                }

                $consumeQty = ((float) $line->quantity / $yield) * $portionQty;
                $this->deductIngredient($businessId, $locationId, (int) $line->ingredient_id, $consumeQty, 'recipe_consumption', $referenceType, $referenceId);
            }
        });
    }

    public function deductIngredient(int $businessId, int $locationId, int $ingredientId, float $quantity, string $movementType = 'issue', ?string $referenceType = null, ?int $referenceId = null): RestaurantNewIngredientStock
    {
        $stock = $this->stockRow($businessId, $locationId, $ingredientId);
        $stock->quantity = (float) $stock->quantity - $quantity;
        $stock->stock_value = max(0, (float) $stock->quantity * (float) $stock->average_cost);
        $stock->save();

        $movement = $this->movement($businessId, $locationId, $ingredientId, $movementType, 0, $quantity, $stock->quantity, (float) $stock->average_cost, null);
        $movement->reference_type = $referenceType;
        $movement->reference_id = $referenceId;
        $movement->save();

        return $stock;
    }

    public function recordWastage(int $businessId, int $locationId, int $ingredientId, float $quantity, string $reason, ?string $notes = null): RestaurantNewWastage
    {
        return DB::transaction(function () use ($businessId, $locationId, $ingredientId, $quantity, $reason, $notes) {
            $stock = $this->deductIngredient($businessId, $locationId, $ingredientId, $quantity, 'wastage');

            return RestaurantNewWastage::create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'ingredient_id' => $ingredientId,
                'wastage_date' => now()->toDateString(),
                'quantity' => $quantity,
                'unit_cost' => $stock->average_cost,
                'total_cost' => $quantity * (float) $stock->average_cost,
                'reason' => $reason,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);
        });
    }

    protected function stockRow(int $businessId, int $locationId, int $ingredientId): RestaurantNewIngredientStock
    {
        return RestaurantNewIngredientStock::firstOrCreate([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'ingredient_id' => $ingredientId,
        ], [
            'quantity' => 0,
            'average_cost' => 0,
            'stock_value' => 0,
        ]);
    }

    protected function movement(int $businessId, int $locationId, int $ingredientId, string $type, float $in, float $out, float $balance, float $unitCost, ?string $notes): RestaurantNewStockMovement
    {
        return RestaurantNewStockMovement::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'ingredient_id' => $ingredientId,
            'movement_type' => $type,
            'quantity_in' => $in,
            'quantity_out' => $out,
            'balance_qty' => $balance,
            'unit_cost' => $unitCost,
            'total_cost' => ($in ?: $out) * $unitCost,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }
}
