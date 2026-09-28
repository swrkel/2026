<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockTransferLookupService
{
    public function __construct(
        protected StockTransferProductBridgeService $productsBridge
    ) {
    }

    public function products(
        int $businessId,
        ?string $term = null,
        int $limit = 30
    ) {
        return $this->productsBridge->searchProducts(
            $businessId,
            $term,
            max(1, min($limit, 50))
        );
    }

    public function variations(int $businessId, int $productId)
    {
        return $this->productsBridge->variations($businessId, $productId);
    }

    public function availableStock(
        int $businessId,
        ?int $locationId,
        ?int $storeId,
        ?int $productId,
        ?int $variationId
    ): float {
        return $this->productsBridge->availableStock(
            $businessId,
            $locationId,
            $storeId,
            $productId,
            $variationId
        );
    }

    public function locations(int $businessId)
    {
        return Cache::remember(
            $this->cacheKey('locations', $businessId),
            now()->addMinutes(30),
            static fn () => DB::table('business_locations')
                ->where('business_id', $businessId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    public function stores(int $businessId, ?int $locationId = null)
    {
        return Cache::remember(
            $this->cacheKey(
                'stores-v3:' . ($locationId ?: 'all'),
                $businessId
            ),
            now()->addMinutes(5),
            function () use ($businessId, $locationId) {
                // Prefer the system Store master because Products New and the
                // standard stock tables use these store IDs.  Do not stop here
                // when it exists but has no matching rows: older standalone
                // installations may still hold their stores in stnew_stores.
                if (
                    Schema::hasTable('stores')
                    && Schema::hasColumn('stores', 'id')
                    && Schema::hasColumn('stores', 'location_id')
                ) {
                    $query = DB::table('stores');

                    if (Schema::hasColumn('stores', 'business_id')) {
                        $query->where('business_id', $businessId);
                    }

                    if (Schema::hasColumn('stores', 'status')) {
                        $query->where('status', 1);
                    }

                    if (Schema::hasColumn('stores', 'deleted_at')) {
                        $query->whereNull('deleted_at');
                    }

                    if ($locationId) {
                        $query->where('location_id', $locationId);
                    }

                    $nameColumn = Schema::hasColumn('stores', 'name')
                        ? 'name'
                        : (Schema::hasColumn('stores', 'code') ? 'code' : 'id');

                    $stores = $query
                        ->select(
                            'id',
                            DB::raw("{$nameColumn} as name"),
                            DB::raw('location_id as location_id')
                        )
                        ->orderBy($nameColumn)
                        ->get();

                    if ($stores->isNotEmpty()) {
                        return $stores;
                    }
                }

                // Compatibility fallback for tenants which were configured
                // with Stock Transfer New's own store master.
                if (! Schema::hasTable('stnew_stores')) {
                    return collect();
                }

                $query = DB::table('stnew_stores')
                    ->where('business_id', $businessId);

                if (Schema::hasColumn('stnew_stores', 'is_active')) {
                    $query->where('is_active', 1);
                }

                if (Schema::hasColumn('stnew_stores', 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }

                if ($locationId && Schema::hasColumn('stnew_stores', 'business_location_id')) {
                    $query->where('business_location_id', $locationId);
                }

                return $query
                    ->select(
                        'id',
                        'name',
                        DB::raw('business_location_id as location_id')
                    )
                    ->orderBy('name')
                    ->get();
            }
        );
    }

    protected function cacheKey(string $type, int $businessId): string
    {
        $tenantId = function_exists('tenant')
            ? (string) (tenant('id') ?? 'unknown')
            : 'unknown';

        return "stn:{$tenantId}:{$businessId}:{$type}";
    }
}
