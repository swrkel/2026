<?php

namespace Modules\ProductsNew\Services\Identity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only compatibility/capability reporting for mixed-version tenant DBs.
 */
class ProductUidCapabilityService
{
    public function __construct(protected ProductUidService $uids) {}

    public function currentDatabase(): array
    {
        $productSupported = $this->uids->productUidSupported();
        $variationSupported = $this->uids->variationUidSupported();

        $missingProductUids = null;
        $missingVariationUids = null;

        if ($productSupported) {
            $missingProductUids = DB::table('products')
                ->where(function ($query) {
                    $query->whereNull(ProductUidService::PRODUCT_UID_COLUMN)
                        ->orWhere(ProductUidService::PRODUCT_UID_COLUMN, '');
                })
                ->count();
        }

        if ($variationSupported) {
            $missingVariationUids = DB::table('variations')
                ->where(function ($query) {
                    $query->whereNull(ProductUidService::VARIATION_UID_COLUMN)
                        ->orWhere(ProductUidService::VARIATION_UID_COLUMN, '');
                })
                ->count();
        }

        return [
            'database' => DB::connection()->getDatabaseName(),
            'feature_version' => ProductUidService::FEATURE_VERSION,
            'state' => $this->state($productSupported, $variationSupported, $missingProductUids, $missingVariationUids),
            'product_uid_supported' => $productSupported,
            'variation_uid_supported' => $variationSupported,
            'missing_product_uids' => $missingProductUids,
            'missing_variation_uids' => $missingVariationUids,
            'registry_connection_configured' => trim((string) config('productsnew.uid.registry_connection', '')) !== '',
            'legacy_safe' => true,
        ];
    }

    protected function state(bool $product, bool $variation, ?int $missingProducts, ?int $missingVariations): string
    {
        if (! $product || ! $variation) {
            return 'legacy';
        }

        if (($missingProducts ?? 0) > 0 || ($missingVariations ?? 0) > 0) {
            return 'uid_ready';
        }

        return 'backfilled';
    }
}
