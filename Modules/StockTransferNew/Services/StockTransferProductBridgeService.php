<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockTransferProductBridgeService
{
    protected static array $tableCache = [];
    protected static array $columnCache = [];

    public function searchProducts(
        int $businessId,
        ?string $term = null,
        int $limit = 30
    ) {
        if (!$this->hasTable('products')) {
            return collect();
        }

        $query = DB::table('products as p')
            ->where('p.business_id', $businessId);

        if ($this->hasColumn('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        if ($this->hasColumn('products', 'is_inactive')) {
            $query->where(function ($q) {
                $q->whereNull('p.is_inactive')
                    ->orWhere('p.is_inactive', 0);
            });
        }

        $term = trim((string) $term);
        if ($term !== '') {
            $search = '%' . $term . '%';

            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', $search);

                if ($this->hasColumn('products', 'sku')) {
                    $q->orWhere('p.sku', 'like', $search);
                }

                if ($this->hasColumn('products', 'barcode')) {
                    $q->orWhere('p.barcode', 'like', $search);
                }
            });
        }

        $columns = ['p.id', 'p.name'];
        if ($this->hasColumn('products', 'sku')) {
            $columns[] = 'p.sku';
        } else {
            $columns[] = DB::raw("'' as sku");
        }

        return $query
            ->select($columns)
            ->orderBy('p.name')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }

    public function variations(int $businessId, int $productId)
    {
        if (!$this->hasTable('variations') || !$this->hasTable('products')) {
            return collect();
        }

        return DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('p.business_id', $businessId)
            ->where('v.product_id', $productId)
            ->select(
                'v.id',
                'v.name',
                'v.sub_sku',
                'v.default_purchase_price'
            )
            ->orderBy('v.name')
            ->get();
    }

    public function availableStock(
        int $businessId,
        int $locationId = null,
        int $storeId = null,
        int $productId = null,
        int $variationId = null
    ): float {
        if ($storeId && $variationId && $this->hasTable('variation_store_details')) {
            $this->assertStoreBelongsToLocation($businessId, $locationId, $storeId);

            $query = DB::table('variation_store_details')
                ->where('store_id', $storeId)
                ->where('variation_id', $variationId);
            if ($productId) {
                $query->where('product_id', $productId);
            }
            return (float) ($query->sum('qty_available') ?: 0);
        }

        if ($variationId && $locationId && $this->hasTable('variation_location_details')) {
            $query = DB::table('variation_location_details')
                ->where('variation_id', $variationId)
                ->where('location_id', $locationId);
            if ($productId) {
                $query->where('product_id', $productId);
            }
            return (float) ($query->sum('qty_available') ?: 0);
        }

        if ($this->hasTable('stnew_stock_balances')) {
            $query = DB::table('stnew_stock_balances')
                ->where('business_id', $businessId);
            if ($locationId) {
                $query->where('business_location_id', $locationId);
            }
            if ($storeId) {
                $query->where('store_id', $storeId);
            }
            if ($productId) {
                $query->where('product_id', $productId);
            }
            if ($variationId) {
                $query->where('variation_id', $variationId);
            }
            return (float) ($query->sum('qty_on_hand') ?: 0);
        }

        return 0.0;
    }

    /**
     * Apply one transfer movement to the shared ERP stock summaries.
     * Both the Location total and the selected Store must move by the same delta.
     */
    public function adjustStandardStock(?int $variationId, ?int $locationId, ?int $storeId, float $delta): void
    {
        if (! $variationId || ! $locationId || abs($delta) < 0.0000001) {
            return;
        }

        if (! $this->hasTable('variations')) {
            throw new \RuntimeException('The product variation table is not available for stock transfer posting.');
        }

        $variation = DB::table('variations')->where('id', $variationId)->first(['id', 'product_id', 'product_variation_id']);
        if (! $variation) {
            throw new \RuntimeException('The selected product variation no longer exists.');
        }

        $businessId = 0;
        if ($this->hasTable('products')) {
            $businessId = (int) (DB::table('products')->where('id', $variation->product_id)->value('business_id') ?: 0);
        }

        if ($storeId) {
            $this->assertStoreBelongsToLocation($businessId, $locationId, $storeId);
        }

        if ($this->hasTable('variation_location_details')) {
            $this->adjustSummaryTable(
                'variation_location_details',
                [
                    'product_id' => (int) $variation->product_id,
                    'variation_id' => (int) $variationId,
                    'location_id' => (int) $locationId,
                ],
                (int) ($variation->product_variation_id ?? 0),
                $delta
            );
        }

        if ($storeId && $this->hasTable('variation_store_details')) {
            $this->adjustSummaryTable(
                'variation_store_details',
                [
                    'product_id' => (int) $variation->product_id,
                    'variation_id' => (int) $variationId,
                    'store_id' => (int) $storeId,
                ],
                (int) ($variation->product_variation_id ?? 0),
                $delta
            );
        }
    }

    /** @param array<string,int> $scope */
    private function adjustSummaryTable(string $table, array $scope, int $productVariationId, float $delta): void
    {
        $rows = DB::table($table)->where($scope)->orderBy('id')->lockForUpdate()->get();
        $current = (float) $rows->sum(fn ($row): float => (float) ($row->qty_available ?? 0));
        $newQty = $current + $delta;

        if ($newQty < -0.0000001) {
            throw new \RuntimeException(sprintf(
                'Stock transfer would create negative stock in %s. Available %.4f; change %.4f.',
                $table,
                $current,
                $delta
            ));
        }

        $payload = ['qty_available' => $newQty, 'updated_at' => now()];
        if ($rows->isNotEmpty()) {
            DB::table($table)->where('id', $rows->first()->id)->update($payload);
            if ($rows->count() > 1) {
                DB::table($table)->whereIn('id', $rows->slice(1)->pluck('id')->all())->update([
                    'qty_available' => 0,
                    'updated_at' => now(),
                ]);
            }
            return;
        }

        $payload = array_merge($scope, $payload, ['created_at' => now()]);
        if ($this->hasColumn($table, 'product_variation_id')) {
            $payload['product_variation_id'] = $productVariationId;
        }
        DB::table($table)->insert($payload);
    }

    private function assertStoreBelongsToLocation(int $businessId, ?int $locationId, int $storeId): void
    {
        if (! $this->hasTable('stores') || ! $locationId) {
            return;
        }

        $query = DB::table('stores')->where('id', $storeId)->where('location_id', $locationId);
        if ($businessId > 0 && $this->hasColumn('stores', 'business_id')) {
            $query->where('business_id', $businessId);
        }
        if (! $query->exists()) {
            throw new \RuntimeException('The selected Store does not belong to the selected transfer Location.');
        }
    }
    protected function hasTable(string $table): bool
    {
        return self::$tableCache[$table]
            ??= Schema::hasTable($table);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        return self::$columnCache[$key]
            ??= Schema::hasColumn($table, $column);
    }
}
