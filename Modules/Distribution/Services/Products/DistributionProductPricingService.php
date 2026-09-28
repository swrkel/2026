<?php

namespace Modules\Distribution\Services\Products;

use Modules\Distribution\Entities\Core\Product;

/**
 * Distribution-owned product pricing seam.
 * Business behavior is preserved; this service only centralizes product price
 * lookup for future module-only maintenance.
 */
class DistributionProductPricingService
{
    public function getDefaultSellPrice($productId): float
    {
        $product = Product::find($productId);

        if (!$product) {
            return 0.0;
        }

        return (float) ($product->default_sell_price ?? $product->selling_price ?? 0);
    }
}
