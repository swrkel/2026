<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenSection;
use Modules\RestaurantNew\Entities\RestaurantNewMenuCategory;
use Modules\RestaurantNew\Entities\RestaurantNewMenuItem;
use Modules\RestaurantNew\Entities\RestaurantNewMenuItemVariant;
use Modules\RestaurantNew\Entities\RestaurantNewMenuModifier;
use Modules\RestaurantNew\Entities\RestaurantNewMenuRecipe;

class MenuManagementService extends CoreSetupService
{
    public function categoriesForDropdown($excludeId = null)
    {
        return $this->scope(RestaurantNewMenuCategory::query())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function kitchenSectionsForDropdown()
    {
        return $this->scope(RestaurantNewKitchenSection::query())
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function saveMenuItem(RestaurantNewMenuItem $item, array $data, array $variants = [], array $modifiers = []): RestaurantNewMenuItem
    {
        return DB::transaction(function () use ($item, $data, $variants, $modifiers) {
            $item->fill($data);
            $item->save();

            $item->variants()->delete();
            foreach ($variants as $variant) {
                if (!empty($variant['name'])) {
                    RestaurantNewMenuItemVariant::create(array_merge($variant, [
                        'business_id' => $data['business_id'],
                        'location_id' => $data['location_id'] ?? null,
                        'menu_item_id' => $item->id,
                        'is_active' => isset($variant['is_active']) ? (bool) $variant['is_active'] : true,
                        'is_default' => isset($variant['is_default']) ? (bool) $variant['is_default'] : false,
                    ]));
                }
            }

            $item->modifiers()->delete();
            foreach ($modifiers as $modifier) {
                if (!empty($modifier['name'])) {
                    RestaurantNewMenuModifier::create(array_merge($modifier, [
                        'business_id' => $data['business_id'],
                        'location_id' => $data['location_id'] ?? null,
                        'menu_item_id' => $item->id,
                        'is_active' => isset($modifier['is_active']) ? (bool) $modifier['is_active'] : true,
                    ]));
                }
            }

            return $item;
        });
    }

    public function saveRecipes(RestaurantNewMenuItem $item, array $recipes): void
    {
        DB::transaction(function () use ($item, $recipes) {
            $item->recipes()->delete();
            foreach ($recipes as $recipe) {
                if (!empty($recipe['ingredient_name']) && !empty($recipe['quantity'])) {
                    RestaurantNewMenuRecipe::create([
                        'business_id' => $item->business_id,
                        'location_id' => $item->location_id,
                        'menu_item_id' => $item->id,
                        'ingredient_name' => $recipe['ingredient_name'],
                        'ingredient_sku' => $recipe['ingredient_sku'] ?? null,
                        'unit' => $recipe['unit'] ?? null,
                        'quantity' => $recipe['quantity'],
                        'wastage_percent' => $recipe['wastage_percent'] ?? 0,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }
}
