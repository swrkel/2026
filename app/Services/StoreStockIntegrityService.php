<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps the shared store stock summary aligned with the transaction location.
 *
 * variation_location_details is the ERP's canonical location balance.  This
 * service is deliberately small and only owns the derived store summary row.
 * It also normalises duplicate store rows without changing their combined qty.
 */
class StoreStockIntegrityService
{
    public function resolveStoreIdForLocation(int $locationId, ?int $storeId = null, ?int $businessId = null): ?int
    {
        if ($locationId <= 0 || ! Schema::hasTable('stores')) {
            return $storeId && $storeId > 0 ? $storeId : null;
        }

        $businessId = $businessId ?: (int) (session('business.id') ?: session('user.business_id') ?: 0);

        $base = DB::table('stores')->where('location_id', $locationId);
        if ($businessId > 0 && Schema::hasColumn('stores', 'business_id')) {
            $base->where('business_id', $businessId);
        }
        if (Schema::hasColumn('stores', 'status')) {
            $base->where('status', 1);
        }
        if (Schema::hasColumn('stores', 'deleted_at')) {
            $base->whereNull('deleted_at');
        }

        // Never write stock to a Store belonging to another Location.
        if ($storeId && $storeId > 0) {
            if ((clone $base)->where('id', $storeId)->exists()) {
                return (int) $storeId;
            }

            Log::critical('Store stock write received a Store outside the transaction Location; resolving a Location-correct Store instead.', [
                'location_id' => $locationId,
                'store_id' => $storeId,
                'business_id' => $businessId ?: null,
            ]);
        }

        $sessionDefault = (int) (session('business.default_store') ?: 0);
        if ($sessionDefault > 0 && (clone $base)->where('id', $sessionDefault)->exists()) {
            return $sessionDefault;
        }

        if (Schema::hasColumn('stores', 'is_main')) {
            $main = (clone $base)->where('is_main', 1)->orderBy('id')->value('id');
            if ($main) {
                return (int) $main;
            }
        }

        $ids = (clone $base)->orderBy('id')->limit(2)->pluck('id');
        if ($ids->count() === 1) {
            return (int) $ids->first();
        }

        // Legacy callers sometimes omit store_id.  Keep their stock inside the
        // correct Location rather than using a business-wide Store from another
        // Location.  On a multi-store Location this deterministic fallback is
        // logged so it can be corrected at the transaction-entry layer later.
        if ($ids->isNotEmpty()) {
            $fallback = (int) $ids->first();
            Log::warning('Store stock write had no unambiguous Store; using the first active Store in the selected Location.', [
                'location_id' => $locationId,
                'resolved_store_id' => $fallback,
                'business_id' => $businessId ?: null,
            ]);
            return $fallback;
        }

        return null;
    }

    public function adjustStoreStock(
        int $locationId,
        int $productId,
        int $variationId,
        float $delta,
        ?int $storeId = null,
        ?int $productVariationId = null,
        bool $allowNegative = true,
        ?int $businessId = null
    ): ?int {
        if (abs($delta) < 0.0000001 || ! Schema::hasTable('variation_store_details')) {
            return $this->resolveStoreIdForLocation($locationId, $storeId, $businessId);
        }

        $storeId = $this->resolveStoreIdForLocation($locationId, $storeId, $businessId);
        if (! $storeId) {
            Log::warning('Store stock row was not updated because no Store exists for the selected Location.', [
                'location_id' => $locationId,
                'product_id' => $productId,
                'variation_id' => $variationId,
            ]);
            return null;
        }

        if (! $productVariationId && Schema::hasTable('variations')) {
            $productVariationId = (int) (DB::table('variations')->where('id', $variationId)->value('product_variation_id') ?: 0);
        }

        $query = DB::table('variation_store_details')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('variation_id', $variationId);

        $rows = $query->orderBy('id')->lockForUpdate()->get();
        $current = (float) $rows->sum(fn ($row) => (float) ($row->qty_available ?? 0));
        $target = $current + $delta;

        if (! $allowNegative && $target < -0.0000001) {
            throw new \RuntimeException(sprintf(
                'Insufficient Store stock. Available %.4f; change %.4f.',
                $current,
                $delta
            ));
        }

        $this->writeCanonicalStoreRow($rows, $storeId, $productId, $variationId, $productVariationId, $target);

        return $storeId;
    }

    public function setStoreStock(
        int $locationId,
        int $productId,
        int $variationId,
        float $quantity,
        ?int $storeId = null,
        ?int $productVariationId = null,
        bool $allowNegative = true,
        ?int $businessId = null
    ): ?int {
        if (! Schema::hasTable('variation_store_details')) {
            return null;
        }

        $storeId = $this->resolveStoreIdForLocation($locationId, $storeId, $businessId);
        if (! $storeId) {
            return null;
        }

        if (! $allowNegative && $quantity < -0.0000001) {
            throw new \RuntimeException('Store stock cannot be negative for this operation.');
        }

        if (! $productVariationId && Schema::hasTable('variations')) {
            $productVariationId = (int) (DB::table('variations')->where('id', $variationId)->value('product_variation_id') ?: 0);
        }

        $rows = DB::table('variation_store_details')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('variation_id', $variationId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $this->writeCanonicalStoreRow($rows, $storeId, $productId, $variationId, $productVariationId, $quantity);

        return $storeId;
    }

    private function writeCanonicalStoreRow($rows, int $storeId, int $productId, int $variationId, ?int $productVariationId, float $quantity): void
    {
        $now = now();
        $payload = [
            'product_id' => $productId,
            'variation_id' => $variationId,
            'store_id' => $storeId,
            'qty_available' => $quantity,
            'updated_at' => $now,
        ];
        if (Schema::hasColumn('variation_store_details', 'product_variation_id')) {
            $payload['product_variation_id'] = $productVariationId ?: 0;
        }

        if ($rows->isEmpty()) {
            if (Schema::hasColumn('variation_store_details', 'created_at')) {
                $payload['created_at'] = $now;
            }
            DB::table('variation_store_details')->insert($payload);
            return;
        }

        $primary = $rows->first();
        DB::table('variation_store_details')->where('id', $primary->id)->update($payload);

        // Keep duplicate IDs intact (safer for unknown legacy references), but
        // neutralise their quantities so SUM(qty_available) remains canonical.
        $duplicateIds = $rows->slice(1)->pluck('id')->all();
        if ($duplicateIds !== []) {
            DB::table('variation_store_details')->whereIn('id', $duplicateIds)->update([
                'qty_available' => 0,
                'updated_at' => $now,
            ]);
            Log::warning('Duplicate variation_store_details rows were normalised while posting stock.', [
                'store_id' => $storeId,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'duplicate_ids' => $duplicateIds,
            ]);
        }
    }
}
