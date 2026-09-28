<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purchase-owned fallback for the legacy App\Services\StoreStockIntegrityService.
 *
 * Newer installations can keep using their application service unchanged.  On an
 * older tenant where that class does not exist, PurchaseServiceProvider aliases
 * this class to the legacy name before the Purchase service graph is resolved.
 *
 * The method intentionally implements only the contract used by Purchase.  It
 * consolidates duplicate variation/store rows into the first row while preserving
 * the total quantity, creates a row only for positive stock when allowed, and
 * refuses a decrement that would make store stock negative.
 */
class StoreStockIntegrityService
{
    public function adjustStoreStock(
        int $locationId,
        int $productId,
        int $variationId,
        float $quantity,
        int $storeId,
        ?int $productVariationId = null,
        bool $allowCreate = true,
        ?int $businessId = null
    ): void {
        if ($storeId <= 0 || $variationId <= 0 || abs($quantity) <= 0.000001) {
            return;
        }

        if (! Schema::hasTable('variation_store_details')) {
            return;
        }

        $query = DB::table('variation_store_details')
            ->where('variation_id', $variationId)
            ->where('store_id', $storeId)
            ->orderBy('id')
            ->lockForUpdate();

        $rows = $query->get();

        if ($rows->isEmpty()) {
            if ($quantity < 0 || ! $allowCreate) {
                throw new \InvalidArgumentException('The store stock record required for this purchase is missing.');
            }

            $resolvedProductVariationId = $this->productVariationId($variationId, $productVariationId);
            $payload = [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'store_id' => $storeId,
                'qty_available' => $quantity,
            ];

            if (Schema::hasColumn('variation_store_details', 'product_variation_id')) {
                $payload['product_variation_id'] = $resolvedProductVariationId;
            }
            if (Schema::hasColumn('variation_store_details', 'location_id')) {
                $payload['location_id'] = $locationId;
            }
            if (Schema::hasColumn('variation_store_details', 'business_id') && $businessId) {
                $payload['business_id'] = $businessId;
            }
            if (Schema::hasColumn('variation_store_details', 'created_at')) {
                $payload['created_at'] = now();
            }
            if (Schema::hasColumn('variation_store_details', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            DB::table('variation_store_details')->insert($payload);
            return;
        }

        $available = (float) $rows->sum(static fn ($row): float => (float) ($row->qty_available ?? 0));
        $newQuantity = $available + $quantity;

        if ($newQuantity < -0.000001) {
            throw new \InvalidArgumentException(sprintf(
                'Insufficient stock is available in the selected store. Available: %.3f; required: %.3f.',
                $available,
                abs($quantity)
            ));
        }

        $updates = ['qty_available' => max(0, $newQuantity)];
        if (Schema::hasColumn('variation_store_details', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('variation_store_details')
            ->where('id', (int) $rows->first()->id)
            ->update($updates);

        if ($rows->count() > 1) {
            $duplicateUpdates = ['qty_available' => 0];
            if (Schema::hasColumn('variation_store_details', 'updated_at')) {
                $duplicateUpdates['updated_at'] = now();
            }
            DB::table('variation_store_details')
                ->whereIn('id', $rows->slice(1)->pluck('id')->all())
                ->update($duplicateUpdates);
        }
    }

    private function productVariationId(int $variationId, ?int $provided): int
    {
        if (($provided ?? 0) > 0) {
            return (int) $provided;
        }

        if (Schema::hasTable('variations') && Schema::hasColumn('variations', 'product_variation_id')) {
            return (int) (DB::table('variations')->where('id', $variationId)->value('product_variation_id') ?? 0);
        }

        return 0;
    }
}
