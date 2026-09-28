<?php

namespace Modules\Distribution\Services\Products;

use Illuminate\Support\Facades\DB;
use Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Entities\Core\Product;
use Modules\Distribution\Entities\Core\Unit;

/**
 * Distribution-owned product lookup layer.
 *
 * This keeps Distribution controllers/services from depending directly on
 * main-system Product/Category/Unit classes. It intentionally preserves the
 * existing ERP tables and behavior while providing a module-local seam for the
 * next separation stages.
 */
class DistributionProductLookupService
{
    public function productsDropdown(int $businessId)
    {
        return Product::where('business_id', $businessId)->pluck('name', 'id');
    }

    public function activeProductsDropdown(int $businessId)
    {
        return Product::where('business_id', $businessId)
            ->where('is_inactive', 0)
            ->pluck('name', 'id');
    }

    public function categoriesDropdown(int $businessId, ?string $type = null)
    {
        $query = Category::where('business_id', $businessId);

        if (!empty($type)) {
            $query->where('category_type', $type);
        }

        return $query->pluck('name', 'id');
    }

    public function parentCategoriesDropdown(int $businessId)
    {
        return Category::where('business_id', $businessId)
            ->where('parent_id', 0)
            ->pluck('name', 'id');
    }

    public function subCategoriesDropdown(int $businessId)
    {
        return Category::where('business_id', $businessId)
            ->where('parent_id', '>', 0)
            ->pluck('name', 'id');
    }

    public function unitsDropdown(int $businessId)
    {
        return Unit::where('business_id', $businessId)->pluck('actual_name', 'id');
    }

    public function findProduct($productId)
    {
        return Product::find($productId);
    }

    public function countProductsInCategory(int $businessId, $categoryId, array $productIds): int
    {
        return DB::table('products')
            ->where('business_id', $businessId)
            ->where('category_id', $categoryId)
            ->whereIn('id', $productIds)
            ->count();
    }
}
