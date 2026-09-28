<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the Other Sales form needs — 8044.
 *
 * Stores, the products in one, and for a chosen product its balance stock and
 * price.
 */
class OtherSaleLookupService
{
    /** Stores at a location. */
    public function stores(int $businessId, int $locationId): array
    {
        if (! Schema::hasTable('stores')) {
            return [];
        }

        return DB::table('stores')
            ->where('business_id', $businessId)
            ->when($locationId > 0 && Schema::hasColumn('stores', 'location_id'),
                fn ($q) => $q->where('location_id', $locationId))
            ->when(Schema::hasColumn('stores', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();
    }

    /**
     * Products that can be sold from a store.
     *
     * Stocked products only - Other Sales is for goods off the shelf. A service
     * with no stock belongs in Other Income, which is what 8045 is for.
     */
    public function products(int $businessId, int $storeId, int $locationId): array
    {
        return DB::table('products')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->where('products.business_id', $businessId)
            ->where('products.enable_stock', 1)
            ->when(Schema::hasColumn('products', 'deleted_at'),
                fn ($q) => $q->whereNull('products.deleted_at'))
            ->orderBy('products.name')
            ->limit(500)
            ->get([
                'products.id as product_id',
                'products.name',
                'products.sku',
                'variations.id as variation_id',
                'variations.sell_price_inc_tax',
            ])
            ->all();
    }

    /**
     * Balance stock and price for one product.
     *
     * Stock is per location, not per business: selling from a store that has
     * none is exactly what the balance figure is there to prevent.
     */
    public function forProduct(int $businessId, int $variationId, int $locationId): array
    {
        $v = DB::table('variations')
            ->join('products', 'products.id', '=', 'variations.product_id')
            ->where('variations.id', $variationId)
            ->where('products.business_id', $businessId)
            ->first([
                'variations.id as variation_id',
                'variations.product_id',
                'variations.sell_price_inc_tax',
                'products.name',
                'products.sku',
            ]);

        if (! $v) {
            return ['found' => false];
        }

        $stock = 0;

        if (Schema::hasTable('variation_location_details')) {
            $stock = (float) (DB::table('variation_location_details')
                ->where('variation_id', $variationId)
                ->when($locationId > 0, fn ($q) => $q->where('location_id', $locationId))
                ->sum('qty_available') ?? 0);
        }

        return [
            'found' => true,
            'variation_id' => $v->variation_id,
            'product_id' => $v->product_id,
            'name' => $v->name,
            'sku' => $v->sku,
            'balance_stock' => $stock,
            'price' => (float) $v->sell_price_inc_tax,
        ];
    }
}
