<?php

namespace Modules\ProductsNew\Services;

use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductOpeningStockService
{
    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected InventoryMovementService $movements,
        protected ProductStatusService $status
    ) {
    }

    public function screenData(ProductsNewProduct $product): array
    {
        $this->assertBusiness($product);

        return [
            'product' => $product,
            'variations' => $this->variations((int) $product->id),
            'locations' => $this->movements->locations(),
            'stores' => $this->stores(),
            'rows' => $this->openingRows((int) $product->id),
            'isInactive' => $this->status->isInactive($product),
        ];
    }

    public function update(ProductsNewProduct $product, array $data): array
    {
        $this->assertBusiness($product);

        if ($this->status->isInactive($product)) {
            throw new DomainException(
                'Opening stock cannot be changed while this product is inactive. Activate the product first.'
            );
        }

        $productId = (int) $product->id;
        $variationId = (int) $data['variation_id'];
        $locationId = (int) $data['location_id'];
        $storeId = ! empty($data['store_id']) ? (int) $data['store_id'] : null;
        $desiredQty = round((float) $data['quantity'], 3);
        $unitCost = round((float) ($data['unit_cost'] ?? 0), 4);

        $variation = $this->variation($productId, $variationId);
        if (! $variation) {
            throw new DomainException('The selected variation does not belong to this product.');
        }

        $this->assertLocation($locationId);
        $this->assertStore($storeId, $locationId);

        $existingOpening = $this->openingQuantity($productId, $variationId, $locationId, $storeId);
        $delta = round($desiredQty - $existingOpening, 3);

        if (abs($delta) < 0.0005) {
            return [
                'existing' => $existingOpening,
                'desired' => $desiredQty,
                'delta' => 0.0,
                'message' => 'Opening stock is already ' . number_format($desiredQty, 3) . '. No stock change was required.',
            ];
        }

        $currentStock = $this->currentStock($variationId, $locationId, $storeId);
        if (round($currentStock + $delta, 3) < -0.0005) {
            throw new DomainException(
                'Opening stock cannot be reduced to ' . number_format($desiredQty, 3)
                . ' because the available stock at this location would become negative.'
            );
        }

        $movementType = $delta > 0
            ? ($existingOpening == 0.0 ? 'opening_stock' : 'opening_stock_adjustment_in')
            : 'opening_stock_adjustment_out';

        $reference = 'PN-OS-P' . $productId . '-' . now()->format('YmdHis');
        $this->movements->record([
            'product_id' => $productId,
            'variation_id' => $variationId,
            'product_variation_id' => (int) ($variation->product_variation_id ?? 0),
            'location_id' => $locationId,
            'store_id' => $storeId,
            'movement_type' => $movementType,
            'movement_date' => $data['opening_date'] ?? now(),
            'qty' => abs($delta),
            'unit_cost' => $unitCost,
            'reference_no' => $reference,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: 'Opening stock added or edited from Products New / List Products.',
        ]);

        return [
            'existing' => $existingOpening,
            'desired' => $desiredQty,
            'delta' => $delta,
            'message' => 'Opening stock updated from ' . number_format($existingOpening, 3)
                . ' to ' . number_format($desiredQty, 3)
                . ' (' . ($delta > 0 ? '+' : '') . number_format($delta, 3) . ').',
        ];
    }

    public function openingRows(int $productId): Collection
    {
        $rows = collect();

        foreach ($this->standaloneOpeningRows($productId) as $row) {
            $key = $this->bucketKey($row->variation_id, $row->location_id, $row->store_id ?? null);
            $rows[$key] = $this->mergeRow($rows->get($key), $row, 'Products New');
        }

        foreach ($this->legacyOpeningRows($productId) as $row) {
            $key = $this->bucketKey($row->variation_id, $row->location_id, null);
            $rows[$key] = $this->mergeRow($rows->get($key), $row, 'Legacy Opening Stock');
        }

        return $rows->values()->map(function (array $row): object {
            $row['current_stock'] = $this->currentStock(
                (int) $row['variation_id'],
                (int) $row['location_id'],
                $row['store_id'] !== null ? (int) $row['store_id'] : null
            );
            $row['sources'] = implode(' + ', array_values(array_unique($row['sources'])));
            return (object) $row;
        })->sortBy(fn ($row) => implode('|', [
            $row->location_name,
            $row->store_name,
            $row->variation_name,
        ]))->values();
    }

    private function standaloneOpeningRows(int $productId): Collection
    {
        $table = 'products_new_inventory_movements';

        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'product_id')
            || ! Schema::hasColumn($table, 'movement_type')
            || ! Schema::hasColumn($table, 'qty')) {
            return collect();
        }

        $columns = Schema::getColumnListing($table);
        $hasStore = in_array('store_id', $columns, true);
        $hasVariation = in_array('variation_id', $columns, true);
        $hasLocation = in_array('location_id', $columns, true);
        $hasCost = in_array('unit_cost', $columns, true);
        $movementDateColumn = in_array('movement_date', $columns, true)
            ? 'movement_date'
            : (in_array('created_at', $columns, true) ? 'created_at' : null);

        $variationColumns = Schema::hasTable('variations')
            ? Schema::getColumnListing('variations')
            : [];
        $canJoinVariation = $hasVariation && in_array('id', $variationColumns, true);
        $hasVariationName = $canJoinVariation && in_array('name', $variationColumns, true);
        $hasVariationSku = $canJoinVariation && in_array('sub_sku', $variationColumns, true);

        $locationColumns = Schema::hasTable('business_locations')
            ? Schema::getColumnListing('business_locations')
            : [];
        $canJoinLocation = $hasLocation && in_array('id', $locationColumns, true);
        $hasLocationName = $canJoinLocation && in_array('name', $locationColumns, true);

        $storeColumns = Schema::hasTable('stores')
            ? Schema::getColumnListing('stores')
            : [];
        $canJoinStore = $hasStore && in_array('id', $storeColumns, true);
        $hasStoreName = $canJoinStore && in_array('name', $storeColumns, true);

        $query = DB::table($table . ' as m')
            ->where('m.product_id', $productId)
            ->whereIn('m.movement_type', [
                'opening_stock',
                'opening_stock_adjustment_in',
                'opening_stock_adjustment_out',
            ]);

        if ($canJoinVariation) {
            $query->leftJoin('variations as v', 'v.id', '=', 'm.variation_id');
        }
        if ($canJoinLocation) {
            $query->leftJoin('business_locations as l', 'l.id', '=', 'm.location_id');
        }
        if ($canJoinStore) {
            $query->leftJoin('stores as s', 's.id', '=', 'm.store_id');
        }
        if (in_array('business_id', $columns, true)) {
            $query->where('m.business_id', $this->guard->businessId());
        }

        $select = [
            DB::raw(($hasVariation ? 'm.variation_id' : 'NULL') . ' as variation_id'),
            DB::raw(($hasLocation ? 'm.location_id' : 'NULL') . ' as location_id'),
            DB::raw(($hasStore ? 'm.store_id' : 'NULL') . ' as store_id'),
            DB::raw(
                "SUM(CASE WHEN m.movement_type = 'opening_stock_adjustment_out' "
                . "THEN -ABS(m.qty) ELSE ABS(m.qty) END) as opening_qty"
            ),
            DB::raw(($hasCost ? 'MAX(m.unit_cost)' : '0') . ' as unit_cost'),
            DB::raw(($movementDateColumn !== null ? 'MAX(m.' . $movementDateColumn . ')' : 'NULL') . ' as opening_date'),
            DB::raw(($hasVariationName ? "COALESCE(v.name, 'Default')" : "'Default'") . ' as variation_name'),
            DB::raw(($hasVariationSku ? "COALESCE(v.sub_sku, '')" : "''") . ' as variation_sku'),
            DB::raw(($hasLocationName ? "COALESCE(l.name, 'Unassigned')" : "'Unassigned'") . ' as location_name'),
            DB::raw(($hasStoreName ? "COALESCE(s.name, '')" : "''") . ' as store_name'),
        ];

        $groupBy = array_values(array_filter([
            $hasVariation ? 'm.variation_id' : null,
            $hasLocation ? 'm.location_id' : null,
            $hasStore ? 'm.store_id' : null,
            $hasVariationName ? 'v.name' : null,
            $hasVariationSku ? 'v.sub_sku' : null,
            $hasLocationName ? 'l.name' : null,
            $hasStoreName ? 's.name' : null,
        ]));

        $query->select($select);
        if ($groupBy !== []) {
            $query->groupBy($groupBy);
        }

        return $query->get();
    }

    private function legacyOpeningRows(int $productId): Collection
    {
        if (! Schema::hasTable('purchase_lines')
            || ! Schema::hasTable('transactions')
            || ! Schema::hasColumn('purchase_lines', 'product_id')
            || ! Schema::hasColumn('purchase_lines', 'transaction_id')
            || ! Schema::hasColumn('purchase_lines', 'quantity')
            || ! Schema::hasColumn('transactions', 'type')) {
            return collect();
        }

        $lineColumns = Schema::getColumnListing('purchase_lines');
        $transactionColumns = Schema::getColumnListing('transactions');
        $hasVariation = in_array('variation_id', $lineColumns, true);
        $hasLocation = in_array('location_id', $transactionColumns, true);
        $costColumn = in_array('purchase_price_inc_tax', $lineColumns, true)
            ? 'purchase_price_inc_tax'
            : (in_array('purchase_price', $lineColumns, true) ? 'purchase_price' : null);
        $dateColumn = in_array('transaction_date', $transactionColumns, true)
            ? 'transaction_date'
            : (in_array('created_at', $transactionColumns, true) ? 'created_at' : null);

        $variationColumns = Schema::hasTable('variations')
            ? Schema::getColumnListing('variations')
            : [];
        $canJoinVariation = $hasVariation && in_array('id', $variationColumns, true);
        $hasVariationName = $canJoinVariation && in_array('name', $variationColumns, true);
        $hasVariationSku = $canJoinVariation && in_array('sub_sku', $variationColumns, true);

        $locationColumns = Schema::hasTable('business_locations')
            ? Schema::getColumnListing('business_locations')
            : [];
        $canJoinLocation = $hasLocation && in_array('id', $locationColumns, true);
        $hasLocationName = $canJoinLocation && in_array('name', $locationColumns, true);

        $query = DB::table('purchase_lines as pl')
            ->join('transactions as t', 't.id', '=', 'pl.transaction_id')
            ->where('pl.product_id', $productId)
            ->where('t.type', 'opening_stock');

        if ($canJoinVariation) {
            $query->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id');
        }
        if ($canJoinLocation) {
            $query->leftJoin('business_locations as l', 'l.id', '=', 't.location_id');
        }
        if (in_array('business_id', $transactionColumns, true)) {
            $query->where('t.business_id', $this->guard->businessId());
        }
        if (in_array('ref_no', $transactionColumns, true)) {
            $query->where(function ($where): void {
                $where->whereNull('t.ref_no')
                    ->orWhere('t.ref_no', 'not like', ProductsNewFinanceStockService::MIRROR_REFERENCE_PREFIX . '%');
            });
        }

        $select = [
            DB::raw(($hasVariation ? 'pl.variation_id' : 'NULL') . ' as variation_id'),
            DB::raw(($hasLocation ? 't.location_id' : 'NULL') . ' as location_id'),
            DB::raw('NULL as store_id'),
            DB::raw('SUM(ABS(pl.quantity)) as opening_qty'),
            DB::raw(($costColumn !== null ? 'MAX(pl.' . $costColumn . ')' : '0') . ' as unit_cost'),
            DB::raw(($dateColumn !== null ? 'MAX(t.' . $dateColumn . ')' : 'NULL') . ' as opening_date'),
            DB::raw(($hasVariationName ? "COALESCE(v.name, 'Default')" : "'Default'") . ' as variation_name'),
            DB::raw(($hasVariationSku ? "COALESCE(v.sub_sku, '')" : "''") . ' as variation_sku'),
            DB::raw(($hasLocationName ? "COALESCE(l.name, 'Unassigned')" : "'Unassigned'") . ' as location_name'),
            DB::raw("'' as store_name"),
        ];

        $groupBy = array_values(array_filter([
            $hasVariation ? 'pl.variation_id' : null,
            $hasLocation ? 't.location_id' : null,
            $hasVariationName ? 'v.name' : null,
            $hasVariationSku ? 'v.sub_sku' : null,
            $hasLocationName ? 'l.name' : null,
        ]));

        $query->select($select);
        if ($groupBy !== []) {
            $query->groupBy($groupBy);
        }

        return $query->get();
    }

    private function openingQuantity(int $productId, int $variationId, int $locationId, ?int $storeId): float
    {
        $row = $this->openingRows($productId)->first(function ($row) use ($variationId, $locationId, $storeId): bool {
            return (int) $row->variation_id === $variationId
                && (int) $row->location_id === $locationId
                && (int) ($row->store_id ?? 0) === (int) ($storeId ?? 0);
        });

        return round((float) ($row->opening_qty ?? 0), 3);
    }

    private function currentStock(int $variationId, int $locationId, ?int $storeId): float
    {
        if ($storeId !== null
            && Schema::hasTable('variation_store_details')
            && Schema::hasColumn('variation_store_details', 'variation_id')
            && Schema::hasColumn('variation_store_details', 'store_id')
            && Schema::hasColumn('variation_store_details', 'qty_available')) {
            return round((float) DB::table('variation_store_details')
                ->where('variation_id', $variationId)
                ->where('store_id', $storeId)
                ->sum('qty_available'), 3);
        }

        if (Schema::hasTable('variation_location_details')
            && Schema::hasColumn('variation_location_details', 'variation_id')
            && Schema::hasColumn('variation_location_details', 'location_id')
            && Schema::hasColumn('variation_location_details', 'qty_available')) {
            return round((float) DB::table('variation_location_details')
                ->where('variation_id', $variationId)
                ->where('location_id', $locationId)
                ->sum('qty_available'), 3);
        }

        return 0.0;
    }

    private function variations(int $productId): Collection
    {
        if (! Schema::hasTable('variations') || ! Schema::hasColumn('variations', 'product_id')) {
            return collect();
        }

        $columns = Schema::getColumnListing('variations');
        $select = ['id', 'product_id'];
        foreach (['product_variation_id', 'name', 'sub_sku', 'dpp_inc_tax', 'default_purchase_price'] as $column) {
            if (in_array($column, $columns, true)) {
                $select[] = $column;
            }
        }

        $query = DB::table('variations')->where('product_id', $productId);
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('id')->get($select);
    }

    private function variation(int $productId, int $variationId): ?object
    {
        return $this->variations($productId)->firstWhere('id', $variationId);
    }

    private function stores(): Collection
    {
        if (! Schema::hasTable('stores')
            || ! Schema::hasColumn('stores', 'id')
            || ! Schema::hasColumn('stores', 'location_id')) {
            return collect();
        }

        $columns = ['id', 'location_id'];
        if (Schema::hasColumn('stores', 'name')) {
            $columns[] = 'name';
        }

        $query = DB::table('stores');
        if (Schema::hasColumn('stores', 'business_id')) {
            $query->where('business_id', $this->guard->businessId());
        }
        if (Schema::hasColumn('stores', 'status')) {
            $query->where('status', 1);
        }

        return $query->orderBy('location_id')->orderBy(Schema::hasColumn('stores', 'name') ? 'name' : 'id')->get($columns);
    }

    private function assertLocation(int $locationId): void
    {
        if (! Schema::hasTable('business_locations')) {
            throw new DomainException('Business locations are not available.');
        }

        $query = DB::table('business_locations')->where('id', $locationId);
        if (Schema::hasColumn('business_locations', 'business_id')) {
            $query->where('business_id', $this->guard->businessId());
        }

        if (! $query->exists()) {
            throw new DomainException('The selected location is not available for this business.');
        }
    }

    private function assertStore(?int $storeId, int $locationId): void
    {
        if ($storeId === null) {
            return;
        }

        if (! Schema::hasTable('stores')) {
            throw new DomainException('Stores are not available in this tenant database.');
        }

        $query = DB::table('stores')->where('id', $storeId);
        if (Schema::hasColumn('stores', 'location_id')) {
            $query->where('location_id', $locationId);
        }
        if (Schema::hasColumn('stores', 'business_id')) {
            $query->where('business_id', $this->guard->businessId());
        }

        if (! $query->exists()) {
            throw new DomainException('The selected store does not belong to the selected location.');
        }
    }

    private function mergeRow(?array $existing, object $row, string $source): array
    {
        $base = $existing ?? [
            'variation_id' => (int) ($row->variation_id ?? 0),
            'variation_name' => (string) ($row->variation_name ?? 'Default'),
            'variation_sku' => (string) ($row->variation_sku ?? ''),
            'location_id' => (int) ($row->location_id ?? 0),
            'location_name' => (string) ($row->location_name ?? 'Unassigned'),
            'store_id' => isset($row->store_id) && $row->store_id !== null ? (int) $row->store_id : null,
            'store_name' => (string) ($row->store_name ?? ''),
            'opening_qty' => 0.0,
            'unit_cost' => 0.0,
            'opening_date' => null,
            'sources' => [],
        ];

        $base['opening_qty'] = round((float) $base['opening_qty'] + (float) ($row->opening_qty ?? 0), 3);
        $base['unit_cost'] = (float) ($row->unit_cost ?? $base['unit_cost']);
        $base['opening_date'] = $row->opening_date ?? $base['opening_date'];
        $base['sources'][] = $source;

        return $base;
    }

    private function bucketKey($variationId, $locationId, $storeId): string
    {
        return (int) $variationId . ':' . (int) $locationId . ':' . (int) ($storeId ?? 0);
    }

    private function assertBusiness(ProductsNewProduct $product): void
    {
        if (Schema::hasColumn('products', 'business_id')
            && (int) $product->business_id !== $this->guard->businessId()) {
            abort(404);
        }
    }
}
