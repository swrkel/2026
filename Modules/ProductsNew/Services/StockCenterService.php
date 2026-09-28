<?php

namespace Modules\ProductsNew\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StockCenterService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}

    /**
     * Build the Stock Center list query without assuming that every tenant has
     * exactly the same Products New schema revision.
     */
    public function query(array $filters = []): Builder
    {
        if (! $this->hasRequiredStockTables()) {
            return $this->emptyStockQuery();
        }

        $hasLocationsTable = $this->tableExists('business_locations')
            && $this->columnExists('business_locations', 'id')
            && $this->columnExists('business_locations', 'name');

        /*
         * Stock Centre must return one logical stock row, not one physical
         * variation_location_details row.
         *
         * A small number of legacy/imported single products can contain more
         * than one core variation or more than one VLD row for the same
         * location.  List Products is product-level, so those records are not
         * visible there, while the old Stock Centre query exposed every raw row
         * and made the same product/SKU appear duplicated with split/mixed qty.
         *
         * Pre-aggregate the core stock before joining display metadata:
         *  - single products => one row per product + location across all of
         *    their internal variations;
         *  - variable/combo products => one row per variation + location;
         *  - duplicate VLD rows for the same logical key are summed once.
         *
         * Normal products that already have one variation/location row are
         * therefore unchanged.
         */
        $stockQuery = $this->stockSummaryQuery();

        $query = DB::query()
            ->fromSub($stockQuery, 'stock')
            ->join('products as p', 'p.id', '=', 'stock.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'stock.representative_variation_id');

        if ($hasLocationsTable) {
            $query->leftJoin('business_locations as l', 'l.id', '=', 'stock.location_id');
        }

        $movementQuery = $this->movementSummaryQuery($filters);
        if ($movementQuery !== null) {
            $query->leftJoinSub($movementQuery, 'pm', function ($join) {
                $join->on('pm.product_id', '=', 'p.id')
                    ->on('pm.stock_variation_key', '=', 'stock.stock_variation_key')
                    ->on('pm.location_id', '=', 'stock.location_id');
            });
        }

        $query->select([
            'p.id as product_id',
            'p.name',
            'stock.representative_variation_id as variation_id',
            'stock.location_id',
            'stock.qty_available',
        ]);
        $query->selectRaw('CASE WHEN stock.stock_variation_key = 0 THEN NULL ELSE stock.representative_variation_id END as details_variation_id');

        $query->selectRaw($this->skuExpression() . ' as sku');
        $query->selectRaw($this->variationNameExpression() . ' as variation_name');
        $query->selectRaw(
            $hasLocationsTable
                ? "COALESCE(l.name, CONCAT('Location #', stock.location_id)) as location_name"
                : "CONCAT('Location #', stock.location_id) as location_name"
        );
        $query->selectRaw(
            $this->columnExists('products', 'alert_quantity')
                ? 'COALESCE(p.alert_quantity, 0) as alert_quantity'
                : '0 as alert_quantity'
        );
        $query->selectRaw(
            $movementQuery !== null
                ? 'COALESCE(pm.pn_movement_qty, 0) as products_new_movement_qty'
                : '0 as products_new_movement_qty'
        );

        $this->guard->applyBusiness($query, 'p.business_id');
        $this->applyAllowedLocations($query, 'stock.location_id');

        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($where) use ($search) {
                $where->where('p.name', 'like', $search);

                if ($this->columnExists('products', 'sku')) {
                    $where->orWhere('p.sku', 'like', $search);
                }

                if ($this->columnExists('variations', 'sub_sku')) {
                    $where->orWhere('v.sub_sku', 'like', $search);
                }
            });
        }

        if (! empty($filters['location_id']) && $filters['location_id'] !== 'all') {
            $query->where('stock.location_id', (int) $filters['location_id']);
        }

        // Products New Stock Centre filters.  Keep these schema-safe because
        // older tenant databases may not yet contain both category columns.
        if (! empty($filters['category_id']) && $this->columnExists('products', 'category_id')) {
            $query->where('p.category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['sub_category_id']) && $this->columnExists('products', 'sub_category_id')) {
            $query->where('p.sub_category_id', (int) $filters['sub_category_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('p.id', (int) $filters['product_id']);
        }

        $stockStatus = (string) ($filters['stock_status'] ?? '');
        if ($stockStatus === 'low') {
            if ($this->columnExists('products', 'alert_quantity')) {
                $query->whereRaw('COALESCE(stock.qty_available, 0) <= COALESCE(p.alert_quantity, 0)')
                    ->whereRaw('COALESCE(p.alert_quantity, 0) > 0');
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($stockStatus === 'negative') {
            $query->whereRaw('COALESCE(stock.qty_available, 0) < 0');
        } elseif ($stockStatus === 'zero') {
            $query->whereRaw('COALESCE(stock.qty_available, 0) = 0');
        }

        return $query
            ->orderBy('p.name')
            ->orderBy('stock.stock_variation_key')
            ->orderBy('stock.location_id');
    }

    /**
     * Normalise Stock Centre filters and apply safe defaults.
     * Date range defaults to Today and Location defaults to the current/first
     * permitted business location when no explicit selection was supplied.
     */
    public function normaliseFilters(array $filters): array
    {
        $today = Carbon::today()->toDateString();
        $from = $this->normaliseDate($filters['from_date'] ?? null, $today);
        $to = $this->normaliseDate($filters['to_date'] ?? null, $today);

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $filters['from_date'] = $from;
        $filters['to_date'] = $to;

        foreach (['category_id', 'sub_category_id', 'product_id', 'location_id'] as $key) {
            $filters[$key] = ! empty($filters[$key]) ? (int) $filters[$key] : null;
        }

        if (empty($filters['location_id'])) {
            $filters['location_id'] = $this->defaultLocationId();
        }

        // Never keep a stale dependent selection after a parent filter changes.
        if (! empty($filters['sub_category_id']) && ! empty($filters['category_id'])
            && $this->tableExists('categories') && $this->columnExists('categories', 'parent_id')) {
            $belongs = DB::table('categories')
                ->where('id', $filters['sub_category_id'])
                ->where('parent_id', $filters['category_id'])
                ->exists();
            if (! $belongs) {
                $filters['sub_category_id'] = null;
                $filters['product_id'] = null;
            }
        }

        return $filters;
    }

    /**
     * Location, category, sub-category and product choices used by Stock Centre.
     * All lookups are business scoped and products honour the selected category
     * and sub-category so Select2 only shows valid dependent choices.
     */
    public function filterLookups(array $filters = []): array
    {
        $locations = collect();
        if ($this->tableExists('business_locations')
            && $this->columnExists('business_locations', 'id')
            && $this->columnExists('business_locations', 'name')) {
            $locationsQuery = DB::table('business_locations')->select(['id', 'name']);
            if ($this->columnExists('business_locations', 'business_id')) {
                $this->guard->applyBusiness($locationsQuery, 'business_locations.business_id');
            }
            if ($this->columnExists('business_locations', 'deleted_at')) {
                $locationsQuery->whereNull('business_locations.deleted_at');
            }
            $this->applyAllowedLocations($locationsQuery, 'business_locations.id');
            $locations = $locationsQuery->orderBy('name')->get();
        }

        $categories = collect();
        $subCategories = collect();
        if ($this->tableExists('categories')
            && $this->columnExists('categories', 'id')
            && $this->columnExists('categories', 'name')) {
            $hasParent = $this->columnExists('categories', 'parent_id');
            $base = DB::table('categories')->select(['id', 'name']);
            if ($this->columnExists('categories', 'business_id')) {
                $this->guard->applyBusiness($base, 'categories.business_id');
            }
            if ($this->columnExists('categories', 'deleted_at')) {
                $base->whereNull('categories.deleted_at');
            }

            $categoryQuery = clone $base;
            if ($hasParent) {
                $categoryQuery->whereRaw('COALESCE(categories.parent_id, 0) = 0');
            }
            $categories = $categoryQuery->orderBy('name')->get();

            if ($hasParent) {
                $subQuery = clone $base;
                $subQuery->addSelect('parent_id')
                    ->whereRaw('COALESCE(categories.parent_id, 0) > 0');
                if (! empty($filters['category_id'])) {
                    $subQuery->where('categories.parent_id', (int) $filters['category_id']);
                }
                $subCategories = $subQuery->orderBy('name')->get();
            }
        }

        $products = collect();
        if ($this->tableExists('products')
            && $this->columnExists('products', 'id')
            && $this->columnExists('products', 'name')
            && $this->columnExists('products', 'business_id')) {
            $productQuery = DB::table('products as p')
                ->select(['p.id', 'p.name'])
                ->selectRaw($this->productSkuOnlyExpression() . ' as sku');
            $this->guard->applyBusiness($productQuery, 'p.business_id');

            if ($this->columnExists('products', 'deleted_at')) {
                $productQuery->whereNull('p.deleted_at');
            }
            if (! empty($filters['category_id']) && $this->columnExists('products', 'category_id')) {
                $productQuery->where('p.category_id', (int) $filters['category_id']);
            }
            if (! empty($filters['sub_category_id']) && $this->columnExists('products', 'sub_category_id')) {
                $productQuery->where('p.sub_category_id', (int) $filters['sub_category_id']);
            }
            $products = $productQuery->orderBy('p.name')->get();
        }

        return [
            'locations' => $locations,
            'categories' => $categories,
            'sub_categories' => $subCategories,
            'products' => $products,
        ];
    }

    /**
     * Return the popup data for a product/variation across every permitted
     * business location and store.
     */
    public function details(int $productId, ?int $variationId = null): array
    {
        $businessId = $this->guard->businessId();

        $productQuery = DB::table('products as p')
            ->where('p.id', $productId)
            ->where('p.business_id', $businessId)
            ->select('p.id', 'p.name');

        $productQuery->selectRaw($this->productSkuOnlyExpression() . ' as sku');
        $product = $productQuery->first();

        if (! $product) {
            throw new NotFoundHttpException('The selected product was not found for this business.');
        }

        $variationName = null;
        if ($variationId !== null && $this->tableExists('variations')) {
            $variation = DB::table('variations as v')
                ->where('v.id', $variationId)
                ->where('v.product_id', $productId)
                ->select('v.id')
                ->selectRaw($this->variationNameExpression() . ' as variation_name')
                ->first();

            if (! $variation) {
                throw new NotFoundHttpException('The selected product variation was not found.');
            }

            $variationName = $variation->variation_name;
        }

        $locations = $this->locationAvailability($productId, $variationId);
        $storesByLocation = $this->storesByLocation($productId, $variationId);
        $batches = $this->batchAvailability($productId, $variationId);

        $locationMap = $locations->keyBy('location_id');
        foreach ($batches as $batch) {
            if (! $locationMap->has($batch->location_id)) {
                $locationMap->put($batch->location_id, (object) [
                    'location_id' => $batch->location_id,
                    'location_name' => $batch->location_name,
                    'available_qty' => 0.0,
                ]);
            }
        }

        foreach ($storesByLocation as $locationId => $stores) {
            if (! $locationMap->has($locationId)) {
                $locationMap->put($locationId, (object) [
                    'location_id' => $locationId,
                    'location_name' => $stores->first()->location_name ?? ('Location #' . $locationId),
                    'available_qty' => (float) $stores->sum('qty_available'),
                ]);
            }
        }

        $locationRows = [];
        foreach ($locationMap->sortBy('location_name') as $location) {
            $locationBatches = $batches->where('location_id', $location->location_id)->values();
            $locationStores = $storesByLocation->get($location->location_id, collect());
            $storeText = $locationStores->isEmpty()
                ? '—'
                : $locationStores->map(function ($store) {
                    return $store->store_name . ' (' . number_format((float) $store->qty_available, 3) . ')';
                })->implode(', ');

            if ($locationBatches->isEmpty()) {
                $locationRows[] = [
                    'location_name' => $location->location_name,
                    'batch_no' => '—',
                    'qty' => (float) $location->available_qty,
                    'stores' => $storeText,
                ];
                continue;
            }

            foreach ($locationBatches as $batch) {
                $locationRows[] = [
                    'location_name' => $location->location_name,
                    'batch_no' => $batch->batch_no,
                    'qty' => (float) $batch->qty,
                    'stores' => $storeText,
                ];
            }
        }

        $totalAvailable = (float) $locations->sum('available_qty');
        if ($locations->isEmpty()) {
            $totalAvailable = (float) $batches->sum('qty');
        }

        return [
            'product' => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'sku' => (string) ($product->sku ?? ''),
                'variation_name' => $variationName,
            ],
            'total_available_qty' => $totalAvailable,
            'location_rows' => $locationRows,
            'batch_rows' => $batches->map(function ($batch) {
                return [
                    'batch_no' => (string) $batch->batch_no,
                    'qty' => (float) $batch->qty,
                    'location_name' => (string) $batch->location_name,
                ];
            })->values()->all(),
        ];
    }

    protected function locationAvailability(int $productId, ?int $variationId): Collection
    {
        if (! $this->hasRequiredStockTables()) {
            return collect();
        }

        $hasLocationsTable = $this->tableExists('business_locations')
            && $this->columnExists('business_locations', 'id')
            && $this->columnExists('business_locations', 'name');

        $query = DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->where('v.product_id', $productId)
            ->select('vld.location_id')
            ->selectRaw('COALESCE(SUM(vld.qty_available), 0) as available_qty')
            ->groupBy('vld.location_id');

        if ($variationId !== null) {
            $query->where('v.id', $variationId);
        }

        $this->applyAllowedLocations($query, 'vld.location_id');

        if ($hasLocationsTable) {
            $query->leftJoin('business_locations as l', 'l.id', '=', 'vld.location_id')
                ->addSelect('l.name as location_name')
                ->groupBy('l.name');
        } else {
            $query->selectRaw("CONCAT('Location #', vld.location_id) as location_name");
        }

        return $query->orderBy('location_name')->get();
    }

    protected function storesByLocation(int $productId, ?int $variationId): Collection
    {
        if (! $this->tableExists('variation_store_details')
            || ! $this->tableExists('stores')
            || ! $this->columnExists('variation_store_details', 'store_id')
            || ! $this->columnExists('variation_store_details', 'qty_available')
            || ! $this->columnExists('stores', 'id')
            || ! $this->columnExists('stores', 'location_id')
            || ! $this->columnExists('stores', 'name')) {
            return collect();
        }

        $query = DB::table('variation_store_details as vsd')
            ->join('stores as s', 's.id', '=', 'vsd.store_id')
            ->select('s.location_id', 's.name as store_name')
            ->selectRaw('COALESCE(SUM(vsd.qty_available), 0) as qty_available')
            ->groupBy('s.location_id', 's.name');

        if ($this->columnExists('variation_store_details', 'product_id')) {
            $query->where('vsd.product_id', $productId);
        } else {
            $query->join('variations as sv', 'sv.id', '=', 'vsd.variation_id')
                ->where('sv.product_id', $productId);
        }

        if ($variationId !== null && $this->columnExists('variation_store_details', 'variation_id')) {
            $query->where('vsd.variation_id', $variationId);
        }

        if ($this->columnExists('stores', 'business_id')) {
            $query->where('s.business_id', $this->guard->businessId());
        }

        if ($this->columnExists('stores', 'status')) {
            $query->where('s.status', 1);
        }

        $this->applyAllowedLocations($query, 's.location_id');

        $hasLocationsTable = $this->tableExists('business_locations')
            && $this->columnExists('business_locations', 'id')
            && $this->columnExists('business_locations', 'name');

        if ($hasLocationsTable) {
            $query->leftJoin('business_locations as sl', 'sl.id', '=', 's.location_id')
                ->addSelect('sl.name as location_name')
                ->groupBy('sl.name');
        } else {
            $query->selectRaw("CONCAT('Location #', s.location_id) as location_name");
        }

        return $query->orderBy('store_name')->get()->groupBy('location_id');
    }

    protected function batchAvailability(int $productId, ?int $variationId): Collection
    {
        if (! $this->tableExists('products_new_batches')) {
            return collect();
        }

        $batchColumn = $this->firstExistingColumn('products_new_batches', [
            'batch_no',
            'batch_number',
            'lot_no',
        ]);
        $qtyColumn = $this->firstExistingColumn('products_new_batches', [
            'available_qty',
            'current_qty',
            'qty_available',
            'quantity',
            'opening_qty',
        ]);

        if ($batchColumn === null || $qtyColumn === null
            || ! $this->columnExists('products_new_batches', 'product_id')
            || ! $this->columnExists('products_new_batches', 'location_id')) {
            return collect();
        }

        $hasLocationsTable = $this->tableExists('business_locations')
            && $this->columnExists('business_locations', 'id')
            && $this->columnExists('business_locations', 'name');

        $query = DB::table('products_new_batches as b')
            ->where('b.product_id', $productId)
            ->select('b.location_id')
            ->selectRaw('b.' . $batchColumn . ' as batch_no')
            ->selectRaw('COALESCE(SUM(b.' . $qtyColumn . '), 0) as qty')
            ->groupBy('b.location_id', 'b.' . $batchColumn);

        if ($this->columnExists('products_new_batches', 'business_id')) {
            $query->where('b.business_id', $this->guard->businessId());
        }

        if ($variationId !== null && $this->columnExists('products_new_batches', 'variation_id')) {
            $query->where('b.variation_id', $variationId);
        }

        if ($this->columnExists('products_new_batches', 'is_active')) {
            $query->where('b.is_active', 1);
        }

        $this->applyAllowedLocations($query, 'b.location_id');

        if ($hasLocationsTable) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 'b.location_id')
                ->addSelect('bl.name as location_name')
                ->groupBy('bl.name');
        } else {
            $query->selectRaw("CONCAT('Location #', b.location_id) as location_name");
        }

        return $query
            ->orderBy('location_name')
            ->orderBy('batch_no')
            ->get();
    }

    /**
     * Aggregate the physical core stock rows into the logical rows displayed by
     * Stock Centre.  See query() for the single-product compatibility rule.
     */
    protected function stockSummaryQuery(): Builder
    {
        $groupExpression = $this->effectiveVariationKeyExpression('sp', 'sv.id');

        $query = DB::table('variation_location_details as svld')
            ->join('variations as sv', 'sv.id', '=', 'svld.variation_id')
            ->join('products as sp', 'sp.id', '=', 'sv.product_id')
            ->select('sp.id as product_id', 'svld.location_id')
            ->selectRaw($groupExpression . ' as stock_variation_key')
            ->selectRaw('MIN(sv.id) as representative_variation_id')
            ->selectRaw('COALESCE(SUM(svld.qty_available), 0) as qty_available')
            ->groupBy('sp.id', 'svld.location_id')
            ->groupByRaw($groupExpression);

        $this->guard->applyBusiness($query, 'sp.business_id');
        $this->applyAllowedLocations($query, 'svld.location_id');

        if ($this->columnExists('variations', 'deleted_at')) {
            $query->whereNull('sv.deleted_at');
        }

        if ($this->columnExists('variation_location_details', 'deleted_at')) {
            $query->whereNull('svld.deleted_at');
        }

        return $query;
    }

    protected function movementSummaryQuery(array $filters = []): ?Builder
    {
        $table = 'products_new_inventory_movements';
        $required = ['product_id', 'variation_id', 'location_id', 'qty'];

        if (! $this->tableExists($table)) {
            return null;
        }

        foreach ($required as $column) {
            if (! $this->columnExists($table, $column)) {
                return null;
            }
        }

        $groupExpression = $this->effectiveVariationKeyExpression('mp', 'm.variation_id');

        $query = DB::table($table . ' as m')
            ->join('products as mp', 'mp.id', '=', 'm.product_id')
            ->select('m.product_id', 'm.location_id')
            ->selectRaw($groupExpression . ' as stock_variation_key')
            ->groupBy('m.product_id', 'm.location_id')
            ->groupByRaw($groupExpression);

        if ($this->columnExists($table, 'movement_type')) {
            $query->selectRaw(
                "SUM(CASE WHEN m.movement_type IN ('opening_stock','stock_in','adjustment_in','return_in','transfer_in','purchase') THEN m.qty ELSE -m.qty END) as pn_movement_qty"
            );
        } else {
            $query->selectRaw('SUM(m.qty) as pn_movement_qty');
        }

        $this->guard->applyBusiness($query, 'mp.business_id');

        if ($this->columnExists($table, 'business_id')) {
            $query->where('m.business_id', $this->guard->businessId());
        }

        $dateColumn = $this->columnExists($table, 'movement_date')
            ? 'movement_date'
            : ($this->columnExists($table, 'created_at') ? 'created_at' : null);
        if ($dateColumn !== null) {
            if (! empty($filters['from_date'])) {
                $query->where('m.' . $dateColumn, '>=', Carbon::parse($filters['from_date'])->startOfDay());
            }
            if (! empty($filters['to_date'])) {
                $query->where('m.' . $dateColumn, '<=', Carbon::parse($filters['to_date'])->endOfDay());
            }
        }

        return $query;
    }

    /**
     * Single products are one stock identity even when legacy/import history has
     * left more than one internal variation behind.  Variable and combo products
     * keep their real variation identity.
     */
    protected function effectiveVariationKeyExpression(string $productAlias, string $variationIdExpression): string
    {
        if (! $this->columnExists('products', 'type')) {
            return $variationIdExpression;
        }

        return "CASE WHEN LOWER(COALESCE({$productAlias}.type, 'single')) = 'single' THEN 0 ELSE {$variationIdExpression} END";
    }

    protected function normaliseDate($value, string $fallback): string
    {
        if (empty($value)) {
            return $fallback;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    protected function defaultLocationId(): ?int
    {
        $allowed = $this->guard->allowedLocationIds();
        $numericAllowed = array_values(array_filter(array_map(
            static fn ($id) => is_numeric($id) ? (int) $id : null,
            $allowed
        )));

        // Respect a current-location session value when it is permitted.
        foreach (['user.location_id', 'location_id', 'business_location_id'] as $key) {
            $sessionLocation = session($key);
            if (is_numeric($sessionLocation)) {
                $sessionLocation = (int) $sessionLocation;
                if (in_array('all', $allowed, true) || in_array($sessionLocation, $numericAllowed, true)) {
                    return $sessionLocation;
                }
            }
        }

        if ($numericAllowed !== []) {
            return $numericAllowed[0];
        }

        if (! $this->tableExists('business_locations')
            || ! $this->columnExists('business_locations', 'id')) {
            return null;
        }

        $query = DB::table('business_locations')->select('id');
        if ($this->columnExists('business_locations', 'business_id')) {
            $this->guard->applyBusiness($query, 'business_locations.business_id');
        }
        if ($this->columnExists('business_locations', 'deleted_at')) {
            $query->whereNull('business_locations.deleted_at');
        }

        $id = $query->orderBy('id')->value('id');
        return $id !== null ? (int) $id : null;
    }

    protected function hasRequiredStockTables(): bool
    {
        return $this->tableExists('variation_location_details')
            && $this->tableExists('variations')
            && $this->tableExists('products')
            && $this->columnExists('variation_location_details', 'variation_id')
            && $this->columnExists('variation_location_details', 'location_id')
            && $this->columnExists('variation_location_details', 'qty_available')
            && $this->columnExists('variations', 'id')
            && $this->columnExists('variations', 'product_id')
            && $this->columnExists('products', 'id')
            && $this->columnExists('products', 'business_id')
            && $this->columnExists('products', 'name');
    }

    protected function emptyStockQuery(): Builder
    {
        $query = DB::table('products as p')
            ->selectRaw("p.id as product_id, p.name, '' as sku, NULL as variation_id, NULL as details_variation_id, '' as variation_name, NULL as location_id, '' as location_name, 0 as qty_available, 0 as alert_quantity, 0 as products_new_movement_qty")
            ->whereRaw('1 = 0');

        if ($this->columnExists('products', 'business_id')) {
            $this->guard->applyBusiness($query, 'p.business_id');
        }

        return $query;
    }

    protected function applyAllowedLocations(Builder $query, string $column): void
    {
        $allowedLocationIds = $this->guard->allowedLocationIds();

        if (in_array('all', $allowedLocationIds, true)) {
            return;
        }

        $allowedLocationIds = array_values(array_filter(array_map('intval', $allowedLocationIds)));
        if ($allowedLocationIds === []) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn($column, $allowedLocationIds);
    }

    protected function skuExpression(): string
    {
        $parts = [];

        if ($this->columnExists('products', 'sku')) {
            $parts[] = "NULLIF(p.sku, '')";
        }
        if ($this->columnExists('variations', 'sub_sku')) {
            $parts[] = "NULLIF(v.sub_sku, '')";
        }

        return $parts === [] ? "''" : 'COALESCE(' . implode(', ', $parts) . ", '')";
    }

    protected function productSkuOnlyExpression(): string
    {
        return $this->columnExists('products', 'sku')
            ? "COALESCE(p.sku, '')"
            : "''";
    }

    protected function variationNameExpression(): string
    {
        if ($this->columnExists('variations', 'name')) {
            return "COALESCE(v.name, '')";
        }

        if ($this->columnExists('variations', 'sub_sku')) {
            return "COALESCE(v.sub_sku, '')";
        }

        return "''";
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
