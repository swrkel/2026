<?php

namespace Modules\StockTakingNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Module-owned bridge to the application's shared product and stock master data.
 * No PHP class from Products, ProductsNew, Stock Adjustment or Stock Transfer is used.
 */
class InventoryBridgeService
{
    private array $tableCache = [];
    private array $columnCache = [];

    public function search(
        int $businessId,
        int $locationId,
        ?int $storeId,
        string $term = '',
        array $scope = [],
        int $limit = 50
    ): array {
        $query = $this->legacyProductQuery($businessId, $locationId, $storeId, $scope);
        if (! $query) {
            return $this->productsNewQuery($businessId, $locationId, $storeId, $term, $limit, $scope);
        }

        if ($term !== '') {
            $like = '%' . trim($term) . '%';
            $query->where(function ($nested) use ($like, $term): void {
                $nested->where('p.name', 'like', $like)
                    ->orWhere('p.sku', 'like', $like)
                    ->orWhere('v.sub_sku', 'like', $like);
                if (ctype_digit($term)) {
                    $nested->orWhere('p.id', (int) $term)->orWhere('v.id', (int) $term);
                }
            });
        }

        return $this->normaliseRows(
            $query->orderBy('p.name')->orderBy('v.id')->limit(max(1, min($limit, 10000)))->get()
        );
    }

    public function snapshot(int $businessId, int $locationId, ?int $storeId, array $scope = []): array
    {
        return $this->search($businessId, $locationId, $storeId, '', $scope, 10000);
    }

    public function currentQty(int $productId, ?int $variationId, int $locationId, ?int $storeId): float
    {
        if ($storeId && $this->hasTable('variation_store_details')) {
            $query = DB::table('variation_store_details')
                ->where('store_id', $storeId)
                ->where('product_id', $productId);
            if ($variationId) {
                $query->where('variation_id', $variationId);
            }
            return (float) $query->sum('qty_available');
        }

        if ($this->hasTable('variation_location_details')) {
            $query = DB::table('variation_location_details')
                ->where('location_id', $locationId)
                ->where('product_id', $productId);
            if ($variationId) {
                $query->where('variation_id', $variationId);
            }
            return (float) $query->sum('qty_available');
        }

        return 0.0;
    }

    public function setCurrentQty(
        int $productId,
        ?int $productVariationId,
        ?int $variationId,
        int $locationId,
        ?int $storeId,
        float $quantity
    ): array {
        if (! $variationId) {
            $variationId = $this->hasTable('variations')
                ? (int) (DB::table('variations')->where('product_id', $productId)->orderBy('id')->value('id') ?: 0)
                : 0;
        }
        if ($variationId <= 0) {
            throw new \RuntimeException('The stock variation could not be identified for this count line.');
        }

        if (! $productVariationId && $this->hasTable('variations')) {
            $productVariationId = (int) (DB::table('variations')->where('id', $variationId)->value('product_variation_id') ?: 0);
        }

        $before = $this->currentQty($productId, $variationId, $locationId, $storeId);

        // A Store count changes two summaries: the selected Store balance and
        // the parent Location total.  The old implementation updated only the
        // Store table when store_id was present, which allowed Store and Location
        // stock to permanently diverge.
        if ($storeId && $this->hasTable('variation_store_details')) {
            $this->setStockTableQty(
                'variation_store_details',
                ['product_id' => $productId, 'variation_id' => $variationId, 'store_id' => $storeId],
                $productVariationId,
                $quantity
            );

            $change = $quantity - $before;
            if ($this->hasTable('variation_location_details')) {
                $locationBefore = $this->currentQty($productId, $variationId, $locationId, null);
                $this->setStockTableQty(
                    'variation_location_details',
                    ['product_id' => $productId, 'variation_id' => $variationId, 'location_id' => $locationId],
                    $productVariationId,
                    $locationBefore + $change
                );
            }

            return [
                'before' => $before,
                'after' => $quantity,
                'change' => $change,
                'table' => 'variation_store_details+variation_location_details',
            ];
        }

        if (! $this->hasTable('variation_location_details')) {
            throw new \RuntimeException('Inventory stock table variation_location_details is not available.');
        }

        $this->setStockTableQty(
            'variation_location_details',
            ['product_id' => $productId, 'variation_id' => $variationId, 'location_id' => $locationId],
            $productVariationId,
            $quantity
        );

        return ['before' => $before, 'after' => $quantity, 'change' => $quantity - $before, 'table' => 'variation_location_details'];
    }

    /** @param array<string,int> $scope */
    private function setStockTableQty(string $table, array $scope, ?int $productVariationId, float $quantity): void
    {
        $rows = DB::table($table)->where($scope)->orderBy('id')->lockForUpdate()->get();
        $payload = ['qty_available' => $quantity, 'updated_at' => now()];

        if ($rows->isNotEmpty()) {
            DB::table($table)->where('id', $rows->first()->id)->update($payload);
            $duplicateIds = $rows->slice(1)->pluck('id')->all();
            if ($duplicateIds !== []) {
                DB::table($table)->whereIn('id', $duplicateIds)->update([
                    'qty_available' => 0,
                    'updated_at' => now(),
                ]);
            }
            return;
        }

        $payload = array_merge($scope, $payload, ['created_at' => now()]);
        if ($this->hasColumn($table, 'product_variation_id')) {
            $payload['product_variation_id'] = $productVariationId ?: 0;
        }
        DB::table($table)->insert($payload);
    }
    private function legacyProductQuery(int $businessId, int $locationId, ?int $storeId, array $scope): ?Builder
    {
        if (! $this->hasTable('products') || ! $this->hasTable('variations')) {
            return null;
        }

        $stockTable = $storeId && $this->hasTable('variation_store_details')
            ? 'variation_store_details'
            : ($this->hasTable('variation_location_details') ? 'variation_location_details' : null);

        $query = DB::table('products as p')
            ->join('variations as v', 'v.product_id', '=', 'p.id');

        if ($this->hasTable('product_variations')) {
            $query->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id');
        }

        if ($stockTable) {
            $stockScopeColumn = $stockTable === 'variation_store_details' ? 'store_id' : 'location_id';
            $stockScopeId = $stockTable === 'variation_store_details' ? $storeId : $locationId;
            $stockSubQuery = DB::table($stockTable . ' as stock_source')
                ->select('stock_source.product_id', 'stock_source.variation_id', DB::raw('SUM(COALESCE(stock_source.qty_available, 0)) AS system_qty'))
                ->where('stock_source.' . $stockScopeColumn, $stockScopeId)
                ->groupBy('stock_source.product_id', 'stock_source.variation_id');
            $query->leftJoinSub($stockSubQuery, 'stock', function ($join): void {
                $join->on('stock.product_id', '=', 'p.id')->on('stock.variation_id', '=', 'v.id');
            });
        }

        $variationGroupExpression = $this->hasTable('product_variations')
            ? "NULLIF(pv.name, 'DUMMY')"
            : 'NULL';
        $unitCostExpression = $this->unitCostExpression();
        $stockExpression = $stockTable ? 'COALESCE(stock.system_qty, 0)' : '0';

        $query->where('p.business_id', $businessId)
            ->select([
                'p.id as product_id',
                'v.product_variation_id',
                'v.id as variation_id',
                'p.name as product_name',
                'p.sku as product_sku',
                'v.sub_sku as variation_sku',
                DB::raw($variationGroupExpression . ' as variation_group'),
                DB::raw("NULLIF(v.name, 'DUMMY') as variation_name"),
                DB::raw($stockExpression . ' as system_qty'),
                DB::raw($unitCostExpression . ' as unit_cost'),
            ]);

        if ($this->hasColumn('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }
        if ($this->hasColumn('products', 'is_inactive')) {
            $query->where(function ($nested): void {
                $nested->whereNull('p.is_inactive')->orWhere('p.is_inactive', 0);
            });
        }
        if ($this->hasColumn('products', 'enable_stock')) {
            $query->where('p.enable_stock', 1);
        }
        if (! empty($scope['category_id']) && $this->hasColumn('products', 'category_id')) {
            $query->where('p.category_id', (int) $scope['category_id']);
        }
        if (! empty($scope['brand_id']) && $this->hasColumn('products', 'brand_id')) {
            $query->where('p.brand_id', (int) $scope['brand_id']);
        }
        if (! empty($scope['product_ids'])) {
            $query->whereIn('p.id', array_values(array_unique(array_map('intval', (array) $scope['product_ids']))));
        }

        return $query;
    }

    private function unitCostExpression(): string
    {
        $parts = [];
        if ($this->hasColumn('variations', 'dpp_inc_tax')) {
            $parts[] = "NULLIF(CAST(v.dpp_inc_tax AS DECIMAL(22,4)), 0)";
        }
        if ($this->hasColumn('variations', 'default_purchase_price')) {
            $parts[] = "CAST(v.default_purchase_price AS DECIMAL(22,4))";
        }
        $parts[] = '0';

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function productsNewQuery(
        int $businessId,
        int $locationId,
        ?int $storeId,
        string $term,
        int $limit,
        array $scope = []
    ): array {
        if (! $this->hasTable('products_new_products')) {
            return [];
        }

        $query = DB::table('products_new_products as p')->where('p.business_id', $businessId);
        if ($term !== '') {
            $query->where(function ($nested) use ($term): void {
                $like = '%' . $term . '%';
                $nested->where('p.name', 'like', $like)->orWhere('p.sku', 'like', $like);
            });
        }
        if (! empty($scope['product_ids'])) {
            $query->whereIn('p.id', array_map('intval', (array) $scope['product_ids']));
        }

        $rows = $query->orderBy('p.name')
            ->limit(max(1, min($limit, 10000)))
            ->get(['p.id as product_id', 'p.name as product_name', 'p.sku as product_sku']);

        return $rows->map(function ($row) use ($locationId, $storeId): array {
            return [
                'product_id' => (int) $row->product_id,
                'product_variation_id' => null,
                'variation_id' => null,
                'product_name' => (string) $row->product_name,
                'sku' => (string) ($row->product_sku ?? ''),
                'variation_name' => '',
                'system_qty' => $this->currentQty((int) $row->product_id, null, $locationId, $storeId),
                'unit_cost' => 0.0,
            ];
        })->all();
    }

    private function normaliseRows($rows): array
    {
        return collect($rows)->map(function ($row): array {
            $parts = array_values(array_filter([
                (string) $row->product_name,
                (string) ($row->variation_group ?? ''),
                (string) ($row->variation_name ?? ''),
            ]));

            return [
                'product_id' => (int) $row->product_id,
                'product_variation_id' => $row->product_variation_id !== null ? (int) $row->product_variation_id : null,
                'variation_id' => $row->variation_id !== null ? (int) $row->variation_id : null,
                'product_name' => implode(' - ', array_unique($parts)),
                'sku' => (string) (($row->variation_sku ?? '') ?: ($row->product_sku ?? '')),
                'system_qty' => (float) $row->system_qty,
                'unit_cost' => (float) $row->unit_cost,
            ];
        })->all();
    }

    private function hasTable(string $table): bool
    {
        if (! array_key_exists($table, $this->tableCache)) {
            try {
                $this->tableCache[$table] = Schema::hasTable($table);
            } catch (\Throwable) {
                $this->tableCache[$table] = false;
            }
        }
        return $this->tableCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (! array_key_exists($key, $this->columnCache)) {
            try {
                $this->columnCache[$key] = $this->hasTable($table) && Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                $this->columnCache[$key] = false;
            }
        }
        return $this->columnCache[$key];
    }
}
