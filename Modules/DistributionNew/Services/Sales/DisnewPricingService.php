<?php

namespace Modules\DistributionNew\Services\Sales;

use Modules\DistributionNew\Models\DisnewPriceListLine;

class DisnewPricingService
{
    public function resolvePrice(int $businessId, int $productId, ?int $variationId = null, float $qty = 1): ?float
    {
        $line = DisnewPriceListLine::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
            ->where('minimum_qty', '<=', $qty)
            ->orderByDesc('minimum_qty')
            ->first();
        return $line ? (float)$line->unit_price : null;
    }
}
