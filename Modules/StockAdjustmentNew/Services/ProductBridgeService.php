<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductBridgeService
{
    private array $tableCache = [];

    private array $columnCache = [];

    /**
     * Search the shared product master without duplicating product data inside
     * Stock Adjustment New. The returned payload is intentionally normalized so
     * the UI can work with both the legacy products table and Products New.
     */
    public function searchProducts(
        string $term,
        ?int $businessId = null,
        ?int $locationId = null,
        ?int $storeId = null,
        int $limit = 25,
        ?int $categoryId = null,
        ?int $subCategoryId = null
    ): array {
        $source = $this->productSource();
        if ($source === null) {
            return [];
        }

        $term = trim($term);
        $limit = max(1, min($limit, 50));

        [$query, $metadata] = $this->baseProductQuery($source, $businessId);
        $this->applyCategoryFilter($query, $metadata, $categoryId, $subCategoryId);
        $this->applySearch($query, $metadata, $term);
        $this->applySearchOrder($query, $metadata, $term);

        $rows = $query->limit($limit)->get();
        $products = [];

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $variationId = $row->variation_id !== null ? (int) $row->variation_id : null;
            $productName = trim((string) $row->product_name);
            $variationName = $this->cleanVariationName((string) ($row->variation_group_name ?? ''));
            $variationValue = $this->cleanVariationName((string) ($row->variation_name ?? ''));
            $nameParts = array_values(array_filter([$productName, $variationName, $variationValue]));
            $displayName = implode(' - ', array_values(array_unique($nameParts)));

            $productSku = trim((string) ($row->product_sku ?? ''));
            $variationSku = trim((string) ($row->variation_sku ?? ''));
            $sku = $variationSku !== '' ? $variationSku : $productSku;
            $identifier = $sku !== '' ? $sku : (string) $productId;
            $key = $productId . ':' . ($variationId ?? 0);

            // Some schemas can produce duplicate rows after variation joins.
            if (isset($products[$key])) {
                continue;
            }

            $products[$key] = [
                'id' => $key,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'identifier' => $identifier,
                'sku' => $sku,
                'name' => $displayName !== '' ? $displayName : ('Product #' . $productId),
                'product_name' => $productName,
                'category_id' => $row->category_id !== null ? (int) $row->category_id : null,
                'sub_category_id' => $row->sub_category_id !== null ? (int) $row->sub_category_id : null,
                'system_qty' => 0.0,
                'unit_cost' => (float) ($row->unit_cost ?? 0),
            ];
        }

        $products = array_values($products);

        return $this->attachCurrentStock($products, $locationId, $storeId);
    }

    /**
     * Resolve a submitted product selection again on the server so the saved
     * product name and SKU always come from the shared product master.
     */
    public function findProduct(
        int $productId,
        ?int $variationId = null,
        ?int $businessId = null,
        ?int $locationId = null,
        ?int $storeId = null
    ): ?array {
        $matches = $this->searchProducts(
            (string) $productId,
            $businessId,
            $locationId,
            $storeId,
            50
        );

        foreach ($matches as $match) {
            if ((int) $match['product_id'] !== $productId) {
                continue;
            }

            if ($variationId !== null && (int) ($match['variation_id'] ?? 0) !== $variationId) {
                continue;
            }

            return $match;
        }

        return null;
    }

    public function getCurrentStock(
        int $productId,
        ?int $variationId,
        int $locationId,
        ?int $storeId = null
    ): float {
        $items = [[
            'product_id' => $productId,
            'variation_id' => $variationId,
            'system_qty' => 0.0,
        ]];

        return (float) ($this->attachCurrentStock($items, $locationId, $storeId)[0]['system_qty'] ?? 0);
    }


    /**
     * Return only batch / lot numbers that still have stock available for the
     * selected product and current business/location/store context.
     *
     * The bridge supports both the existing purchase_lines lot structure and
     * the standalone Products New products_new_batches structure.
     */
    public function getAvailableBatches(
        int $productId,
        ?int $variationId = null,
        ?int $businessId = null,
        ?int $locationId = null,
        ?int $storeId = null,
        ?string $selectionMethod = null
    ): array {
        $source = $this->productSource();
        $sourceTable = $source['table'] ?? null;

        // Do not fall through to another product master merely because the
        // selected product currently has no batch stock. Numeric product IDs
        // can exist in both masters, and such a fall-through could show a batch
        // belonging to a different product. Use the alternate source only when
        // the preferred batch structure is not installed in this tenant.
        if ($sourceTable === 'products_new_products') {
            $rows = $this->supportsProductsNewBatches()
                ? $this->productsNewBatchRows($productId, $variationId, $businessId, $locationId, $storeId)
                : $this->legacyPurchaseBatchRows($productId, $variationId, $businessId, $locationId, $storeId);
        } else {
            $rows = $this->supportsLegacyPurchaseBatches()
                ? $this->legacyPurchaseBatchRows($productId, $variationId, $businessId, $locationId, $storeId)
                : $this->productsNewBatchRows($productId, $variationId, $businessId, $locationId, $storeId);
        }

        return $this->sortBatches($this->normaliseBatchRows($rows), $selectionMethod);
    }

    public function findAvailableBatch(
        int $productId,
        ?int $variationId,
        string $batchNo,
        ?int $businessId = null,
        ?int $locationId = null,
        ?int $storeId = null,
        ?string $selectionMethod = null
    ): ?array {
        $batchNo = trim($batchNo);
        if ($batchNo === '') {
            return null;
        }

        foreach ($this->getAvailableBatches(
            $productId,
            $variationId,
            $businessId,
            $locationId,
            $storeId,
            $selectionMethod
        ) as $batch) {
            if (strcasecmp((string) $batch['batch_no'], $batchNo) === 0) {
                return $batch;
            }
        }

        return null;
    }

    private function productsNewBatchRows(
        int $productId,
        ?int $variationId,
        ?int $businessId,
        ?int $locationId,
        ?int $storeId
    ): array {
        $table = 'products_new_batches';
        if (! $this->hasTable($table)) {
            return [];
        }

        $idColumn = $this->firstColumn($table, ['id', 'batch_id']);
        $productColumn = $this->firstColumn($table, ['product_id']);
        $variationColumn = $this->firstColumn($table, ['variation_id', 'product_variation_id']);
        $businessColumn = $this->firstColumn($table, ['business_id']);
        $locationColumn = $this->firstColumn($table, ['location_id', 'business_location_id']);
        $storeColumn = $this->firstColumn($table, ['store_id']);
        $batchColumn = $this->firstColumn($table, ['batch_no', 'batch_number', 'lot_number', 'lot_no']);
        $lotColumn = $this->firstColumn($table, ['lot_no', 'lot_number', 'supplier_batch_no']);
        $expiryColumn = $this->firstColumn($table, ['expiry_at', 'expiry_date', 'exp_date']);
        $costColumn = $this->firstColumn($table, ['cost_price', 'unit_cost', 'purchase_price']);

        if ($idColumn === null || $productColumn === null || $batchColumn === null) {
            return [];
        }

        $availableColumn = $this->firstColumn($table, ['available_qty', 'qty_available']);
        $currentColumn = $this->firstColumn($table, ['current_qty', 'current_stock', 'stock_qty', 'quantity']);
        $reservedColumn = $this->firstColumn($table, ['reserved_qty', 'quantity_reserved']);

        if ($availableColumn !== null) {
            $availableExpression = 'COALESCE(b.' . $availableColumn . ', 0)';
        } elseif ($currentColumn !== null && $reservedColumn !== null) {
            $availableExpression = '(COALESCE(b.' . $currentColumn . ', 0) - COALESCE(b.' . $reservedColumn . ', 0))';
        } elseif ($currentColumn !== null) {
            $availableExpression = 'COALESCE(b.' . $currentColumn . ', 0)';
        } else {
            return [];
        }

        $query = DB::table($table . ' as b')
            ->where('b.' . $productColumn, $productId)
            ->whereNotNull('b.' . $batchColumn)
            ->whereRaw("TRIM(COALESCE(b.{$batchColumn}, '')) <> ''")
            ->whereRaw($availableExpression . ' > 0');

        if ($variationId !== null && $variationColumn !== null) {
            $query->where('b.' . $variationColumn, $variationId);
        }

        if ($businessId !== null && $businessColumn !== null) {
            $query->where('b.' . $businessColumn, $businessId);
        }

        if ($locationId !== null && $locationColumn !== null) {
            $query->where('b.' . $locationColumn, $locationId);
        }

        if ($storeId !== null && $storeColumn !== null) {
            $query->where('b.' . $storeColumn, $storeId);
        }

        $this->applyActiveScope($query, $table, 'b');

        return $query->select([
            'b.' . $idColumn . ' as source_id',
            'b.' . $batchColumn . ' as batch_no',
            $lotColumn !== null
                ? 'b.' . $lotColumn . ' as lot_no'
                : DB::raw('NULL as lot_no'),
            $expiryColumn !== null
                ? 'b.' . $expiryColumn . ' as expiry_date'
                : DB::raw('NULL as expiry_date'),
            DB::raw($availableExpression . ' as available_qty'),
            $costColumn !== null
                ? 'b.' . $costColumn . ' as unit_cost'
                : DB::raw('0 as unit_cost'),
        ])->get()->all();
    }

    private function legacyPurchaseBatchRows(
        int $productId,
        ?int $variationId,
        ?int $businessId,
        ?int $locationId,
        ?int $storeId
    ): array {
        $lineTable = 'purchase_lines';
        $transactionTable = 'transactions';

        if (! $this->hasTable($lineTable) || ! $this->hasTable($transactionTable)) {
            return [];
        }

        $idColumn = $this->firstColumn($lineTable, ['id', 'purchase_line_id']);
        $transactionColumn = $this->firstColumn($lineTable, ['transaction_id']);
        $productColumn = $this->firstColumn($lineTable, ['product_id']);
        $variationColumn = $this->firstColumn($lineTable, ['variation_id', 'product_variation_id']);
        $batchColumn = $this->firstColumn($lineTable, ['lot_number', 'batch_no', 'batch_number', 'lot_no']);
        $expiryColumn = $this->firstColumn($lineTable, ['exp_date', 'expiry_date', 'expiry_at']);
        $quantityColumn = $this->firstColumn($lineTable, ['quantity', 'qty']);
        $soldColumn = $this->firstColumn($lineTable, ['quantity_sold', 'qty_sold']);
        $adjustedColumn = $this->firstColumn($lineTable, ['quantity_adjusted', 'qty_adjusted']);
        $returnedColumn = $this->firstColumn($lineTable, ['quantity_returned', 'qty_returned']);
        $costColumn = $this->firstColumn($lineTable, [
            'purchase_price_inc_tax',
            'purchase_price',
            'pp_without_discount',
            'unit_cost',
        ]);

        $transactionIdColumn = $this->firstColumn($transactionTable, ['id', 'transaction_id']);
        $businessColumn = $this->firstColumn($transactionTable, ['business_id']);
        $locationColumn = $this->firstColumn($transactionTable, ['location_id', 'business_location_id']);
        $transactionStoreColumn = $this->firstColumn($transactionTable, ['store_id']);
        $lineStoreColumn = $this->firstColumn($lineTable, ['store_id']);

        if (
            $idColumn === null
            || $transactionColumn === null
            || $productColumn === null
            || $batchColumn === null
            || $quantityColumn === null
            || $transactionIdColumn === null
        ) {
            return [];
        }

        $deductions = [];
        foreach ([$soldColumn, $adjustedColumn, $returnedColumn] as $column) {
            if ($column !== null) {
                $deductions[] = 'COALESCE(pl.' . $column . ', 0)';
            }
        }

        $availableExpression = 'COALESCE(pl.' . $quantityColumn . ', 0)';
        if ($deductions !== []) {
            $availableExpression .= ' - ' . implode(' - ', $deductions);
        }
        $availableExpression = '(' . $availableExpression . ')';

        $query = DB::table($lineTable . ' as pl')
            ->join(
                $transactionTable . ' as t',
                'pl.' . $transactionColumn,
                '=',
                't.' . $transactionIdColumn
            )
            ->where('pl.' . $productColumn, $productId)
            ->whereNotNull('pl.' . $batchColumn)
            ->whereRaw("TRIM(COALESCE(pl.{$batchColumn}, '')) <> ''")
            ->whereRaw($availableExpression . ' > 0');

        if ($variationId !== null && $variationColumn !== null) {
            $query->where('pl.' . $variationColumn, $variationId);
        }

        if ($businessId !== null && $businessColumn !== null) {
            $query->where('t.' . $businessColumn, $businessId);
        }

        if ($locationId !== null && $locationColumn !== null) {
            $query->where('t.' . $locationColumn, $locationId);
        }

        if ($storeId !== null) {
            if ($transactionStoreColumn !== null) {
                $query->where('t.' . $transactionStoreColumn, $storeId);
            } elseif ($lineStoreColumn !== null) {
                $query->where('pl.' . $lineStoreColumn, $storeId);
            }
        }

        if ($this->hasColumn($lineTable, 'deleted_at')) {
            $query->whereNull('pl.deleted_at');
        }

        if ($this->hasColumn($transactionTable, 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }

        return $query->select([
            'pl.' . $idColumn . ' as source_id',
            'pl.' . $batchColumn . ' as batch_no',
            DB::raw('NULL as lot_no'),
            $expiryColumn !== null
                ? 'pl.' . $expiryColumn . ' as expiry_date'
                : DB::raw('NULL as expiry_date'),
            DB::raw($availableExpression . ' as available_qty'),
            $costColumn !== null
                ? 'pl.' . $costColumn . ' as unit_cost'
                : DB::raw('0 as unit_cost'),
        ])->get()->all();
    }

    private function supportsProductsNewBatches(): bool
    {
        return $this->hasTable('products_new_batches')
            && $this->firstColumn('products_new_batches', ['id', 'batch_id']) !== null
            && $this->firstColumn('products_new_batches', ['product_id']) !== null
            && $this->firstColumn('products_new_batches', ['batch_no', 'batch_number', 'lot_number', 'lot_no']) !== null
            && (
                $this->firstColumn('products_new_batches', ['available_qty', 'qty_available']) !== null
                || $this->firstColumn('products_new_batches', ['current_qty', 'current_stock', 'stock_qty', 'quantity']) !== null
            );
    }

    private function supportsLegacyPurchaseBatches(): bool
    {
        return $this->hasTable('purchase_lines')
            && $this->hasTable('transactions')
            && $this->firstColumn('purchase_lines', ['id', 'purchase_line_id']) !== null
            && $this->firstColumn('purchase_lines', ['transaction_id']) !== null
            && $this->firstColumn('purchase_lines', ['product_id']) !== null
            && $this->firstColumn('purchase_lines', ['lot_number', 'batch_no', 'batch_number', 'lot_no']) !== null
            && $this->firstColumn('purchase_lines', ['quantity', 'qty']) !== null;
    }

    /**
     * @param array<int, array<string, mixed>> $batches
     * @return array<int, array<string, mixed>>
     */
    private function sortBatches(array $batches, ?string $selectionMethod): array
    {
        $method = in_array($selectionMethod, ['fefo', 'fifo', 'manual'], true)
            ? $selectionMethod
            : 'fefo';

        if ($method === 'fifo') {
            usort($batches, static function (array $left, array $right): int {
                $leftId = (int) ($left['source_id'] ?? PHP_INT_MAX);
                $rightId = (int) ($right['source_id'] ?? PHP_INT_MAX);

                return $leftId !== $rightId
                    ? $leftId <=> $rightId
                    : strcasecmp((string) $left['batch_no'], (string) $right['batch_no']);
            });
        } elseif ($method === 'manual') {
            usort($batches, static fn (array $left, array $right): int =>
                strcasecmp((string) $left['batch_no'], (string) $right['batch_no'])
            );
        }

        return $batches;
    }

    private function normaliseBatchRows(array $rows): array
    {
        $batches = [];

        foreach ($rows as $row) {
            $batchNo = trim((string) ($row->batch_no ?? ''));
            $availableQty = (float) ($row->available_qty ?? 0);

            if ($batchNo === '' || $availableQty <= 0) {
                continue;
            }

            $key = strtolower($batchNo);
            $lotNo = trim((string) ($row->lot_no ?? ''));
            $expiryDate = trim((string) ($row->expiry_date ?? ''));
            $unitCost = (float) ($row->unit_cost ?? 0);

            if (! isset($batches[$key])) {
                $batches[$key] = [
                    'id' => (string) ($row->source_id ?? $batchNo),
                    'source_id' => isset($row->source_id) ? (int) $row->source_id : null,
                    'batch_no' => $batchNo,
                    'lot_numbers' => [],
                    'expiry_date' => $expiryDate !== '' ? $expiryDate : null,
                    'available_qty' => 0.0,
                    'unit_cost' => $unitCost,
                ];
            }

            $batches[$key]['available_qty'] += $availableQty;

            if ($lotNo !== '' && ! in_array($lotNo, $batches[$key]['lot_numbers'], true)) {
                $batches[$key]['lot_numbers'][] = $lotNo;
            }

            if (
                $expiryDate !== ''
                && (
                    $batches[$key]['expiry_date'] === null
                    || $expiryDate < $batches[$key]['expiry_date']
                )
            ) {
                $batches[$key]['expiry_date'] = $expiryDate;
            }

            if ((float) $batches[$key]['unit_cost'] === 0.0 && $unitCost > 0) {
                $batches[$key]['unit_cost'] = $unitCost;
            }
        }

        $normalised = [];
        foreach ($batches as $batch) {
            $batch['lot_no'] = implode(', ', $batch['lot_numbers']);
            unset($batch['lot_numbers']);

            $label = $batch['batch_no'];
            if ($batch['lot_no'] !== '' && strcasecmp($batch['lot_no'], $batch['batch_no']) !== 0) {
                $label .= ' / Lot ' . $batch['lot_no'];
            }

            $batch['label'] = $label;
            $normalised[] = $batch;
        }

        usort($normalised, static function (array $left, array $right): int {
            $leftExpiry = $left['expiry_date'] ?? '9999-12-31';
            $rightExpiry = $right['expiry_date'] ?? '9999-12-31';
            $expiryComparison = strcmp($leftExpiry, $rightExpiry);

            return $expiryComparison !== 0
                ? $expiryComparison
                : strcasecmp((string) $left['batch_no'], (string) $right['batch_no']);
        });

        return $normalised;
    }

    private function productSource(): ?array
    {
        foreach (['products', 'products_new_products'] as $table) {
            if (! $this->hasTable($table)) {
                continue;
            }

            $id = $this->firstColumn($table, ['id', 'product_id']);
            $name = $this->firstColumn($table, ['name', 'product_name', 'title']);
            if ($id === null || $name === null) {
                continue;
            }

            return [
                'table' => $table,
                'id' => $id,
                'name' => $name,
                'sku' => $this->firstColumn($table, ['sku', 'product_sku', 'code']),
                'business_id' => $this->firstColumn($table, ['business_id']),
                'category_id' => $this->firstColumn($table, ['category_id', 'product_category_id']),
                'sub_category_id' => $this->firstColumn($table, ['sub_category_id', 'subcategory_id', 'product_sub_category_id']),
                /*
                 * IS2158: dpp_inc_tax FIRST - the tax-INCLUSIVE purchase price.
                 *
                 * firstColumn() returns the first name that exists on the table,
                 * so the order here decides which price becomes the unit cost.
                 * default_purchase_price came first, and that column is the
                 * EXCLUSIVE price - so Create Adjustment showed a unit cost
                 * without tax, and the Dashboard's Cost (qty x unit cost) was
                 * short by the tax on every line.
                 *
                 * dpp_inc_tax is the inclusive figure shown as "Default Purchase
                 * Price (Incl. Tax)" on the product screen, which is what the
                 * cost should be based on.
                 *
                 * default_purchase_price is KEPT as the fallback: on a schema
                 * without dpp_inc_tax it is the only purchase price there is, and
                 * an approximate cost is better than none.
                 */
                'unit_cost' => $this->firstColumn($table, [
                    'dpp_inc_tax',
                    'default_purchase_price',
                    'unit_cost',
                    'purchase_price',
                    'cost',
                ]),
            ];
        }

        return null;
    }

    private function baseProductQuery(array $source, ?int $businessId): array
    {
        $table = $source['table'];
        $query = DB::table($table . ' as p');
        $hasVariations = $table === 'products'
            && $this->hasTable('variations')
            && $this->hasColumn('variations', 'id')
            && $this->hasColumn('variations', 'product_id');

        $hasProductVariations = $hasVariations
            && $this->hasColumn('variations', 'product_variation_id')
            && $this->hasTable('product_variations')
            && $this->hasColumn('product_variations', 'id');

        if ($hasVariations) {
            $query->leftJoin('variations as v', 'v.product_id', '=', 'p.' . $source['id']);
        }

        if ($hasProductVariations) {
            $query->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id');
        }

        $variationSku = $hasVariations
            ? $this->firstColumn('variations', ['sub_sku', 'sku', 'code'])
            : null;
        $variationName = $hasVariations
            ? $this->firstColumn('variations', ['name', 'variation_value', 'value'])
            : null;
        $variationCost = $hasVariations
            ? $this->firstColumn('variations', [
                // IS2158: same order as above - inclusive price first.
                'dpp_inc_tax',
                'default_purchase_price',
                'unit_cost',
                'purchase_price',
                'cost',
            ])
            : null;
        $variationGroupName = $hasProductVariations
            ? $this->firstColumn('product_variations', ['name', 'variation_name', 'title'])
            : null;

        $query->select([
            'p.' . $source['id'] . ' as product_id',
            'p.' . $source['name'] . ' as product_name',
            $source['sku'] !== null
                ? 'p.' . $source['sku'] . ' as product_sku'
                : DB::raw('NULL as product_sku'),
            $source['category_id'] !== null
                ? 'p.' . $source['category_id'] . ' as category_id'
                : DB::raw('NULL as category_id'),
            $source['sub_category_id'] !== null
                ? 'p.' . $source['sub_category_id'] . ' as sub_category_id'
                : DB::raw('NULL as sub_category_id'),
            $hasVariations
                ? 'v.id as variation_id'
                : DB::raw('NULL as variation_id'),
            $variationSku !== null
                ? 'v.' . $variationSku . ' as variation_sku'
                : DB::raw('NULL as variation_sku'),
            $variationName !== null
                ? 'v.' . $variationName . ' as variation_name'
                : DB::raw('NULL as variation_name'),
            $variationGroupName !== null
                ? 'pv.' . $variationGroupName . ' as variation_group_name'
                : DB::raw('NULL as variation_group_name'),
            $variationCost !== null
                ? 'v.' . $variationCost . ' as unit_cost'
                : ($source['unit_cost'] !== null
                    ? 'p.' . $source['unit_cost'] . ' as unit_cost'
                    : DB::raw('0 as unit_cost')),
        ]);

        if ($businessId !== null && $source['business_id'] !== null) {
            $query->where('p.' . $source['business_id'], $businessId);
        }

        $this->applyActiveScope($query, $table, 'p');
        $enableStockColumn = $this->firstColumn($table, ['enable_stock', 'stock_enabled', 'is_stock_item']);
        if ($enableStockColumn !== null) {
            $query->where(function (Builder $stockEnabled) use ($enableStockColumn): void {
                $stockEnabled->whereNull('p.' . $enableStockColumn)
                    ->orWhere('p.' . $enableStockColumn, 1);
            });
        }
        if ($hasVariations) {
            $this->applyActiveScope($query, 'variations', 'v');
        }

        return [$query, [
            'product_id' => 'p.' . $source['id'],
            'product_name' => 'p.' . $source['name'],
            'product_sku' => $source['sku'] !== null ? 'p.' . $source['sku'] : null,
            'category_id' => $source['category_id'] !== null ? 'p.' . $source['category_id'] : null,
            'sub_category_id' => $source['sub_category_id'] !== null ? 'p.' . $source['sub_category_id'] : null,
            'variation_id' => $hasVariations ? 'v.id' : null,
            'variation_sku' => $variationSku !== null ? 'v.' . $variationSku : null,
            'variation_name' => $variationName !== null ? 'v.' . $variationName : null,
            'variation_group_name' => $variationGroupName !== null ? 'pv.' . $variationGroupName : null,
        ]];
    }

    private function applyCategoryFilter(
        Builder $query,
        array $metadata,
        ?int $categoryId,
        ?int $subCategoryId
    ): void {
        if ($categoryId !== null && $metadata['category_id'] !== null) {
            $query->where($metadata['category_id'], $categoryId);
        }

        if ($subCategoryId !== null && $metadata['sub_category_id'] !== null) {
            $query->where($metadata['sub_category_id'], $subCategoryId);
        }
    }

    private function applySearch(Builder $query, array $metadata, string $term): void
    {
        if ($term === '') {
            return;
        }

        $like = '%' . $term . '%';
        $fields = array_values(array_filter([
            $metadata['product_name'],
            $metadata['product_sku'],
            $metadata['variation_sku'],
            $metadata['variation_name'],
            $metadata['variation_group_name'],
        ]));

        $query->where(function (Builder $search) use ($fields, $metadata, $term, $like): void {
            $search->where($metadata['product_id'], 'like', $like);

            foreach ($fields as $field) {
                $search->orWhere($field, 'like', $like);
            }

            if ($metadata['variation_id'] !== null && ctype_digit($term)) {
                $search->orWhere($metadata['variation_id'], (int) $term);
            }
        });
    }

    private function applySearchOrder(Builder $query, array $metadata, string $term): void
    {
        if ($term !== '') {
            $exactConditions = [];
            $bindings = [];

            if (ctype_digit($term)) {
                $exactConditions[] = $metadata['product_id'] . ' = ?';
                $bindings[] = (int) $term;

                if ($metadata['variation_id'] !== null) {
                    $exactConditions[] = $metadata['variation_id'] . ' = ?';
                    $bindings[] = (int) $term;
                }
            }

            foreach ([$metadata['product_sku'], $metadata['variation_sku']] as $skuField) {
                if ($skuField !== null) {
                    $exactConditions[] = $skuField . ' = ?';
                    $bindings[] = $term;
                }
            }

            if ($exactConditions !== []) {
                $query->orderByRaw(
                    'CASE WHEN ' . implode(' OR ', $exactConditions) . ' THEN 0 ELSE 1 END',
                    $bindings
                );
            }
        }

        $query->orderBy($metadata['product_name']);
        if ($metadata['variation_id'] !== null) {
            $query->orderBy($metadata['variation_id']);
        }
    }

    private function attachCurrentStock(array $products, ?int $locationId, ?int $storeId): array
    {
        if ($products === []) {
            return $products;
        }

        /*
         * S756 - one canonical System Qty source.
         *
         * Products New / Stock Centre, Product History and the host Product
         * Transaction Report all reconcile to the shared LOCATION stock table
         * (variation_location_details).  Stock Adjustment New previously switched
         * to variation_store_details as soon as a Store was selected.  Those two
         * physical summaries can legitimately be out of sync on older/imported
         * data, which is why the adjustment screen could show 20 while Products
         * New showed -150 for the same product/location.
         *
         * System Qty must therefore use the same location balance as the other
         * stock pages.  The selected Store is still validated, stored on the
         * adjustment and used by the posting/batch logic; it simply does not
         * replace the canonical location balance shown in System Qty.
         *
         * Products New also treats a "single" product as one stock identity even
         * if legacy/import history has left more than one internal variation.  We
         * mirror that rule here: single products use the product-level sum across
         * their internal variations, while variable/combo products keep the
         * selected variation balance.
         */
        $table = 'variation_location_details';

        if (! $this->hasTable($table)) {
            return $products;
        }

        $productColumn = $this->firstColumn($table, ['product_id']);
        $locationColumn = $this->firstColumn($table, ['location_id', 'business_location_id']);
        $variationColumn = $this->firstColumn($table, ['variation_id', 'product_variation_id']);
        $quantityColumn = $this->firstColumn($table, [
            'qty_available',
            'quantity',
            'stock_qty',
            'current_stock',
        ]);

        if ($locationColumn === null || $quantityColumn === null) {
            return $products;
        }

        $productIds = array_values(array_unique(array_map(
            static fn (array $product): int => (int) $product['product_id'],
            $products
        )));
        $productIds = array_values(array_filter($productIds, static fn (int $id): bool => $id > 0));

        if ($productIds === []) {
            return $products;
        }

        $stockQuery = DB::table($table . ' as vld');
        $productExpression = null;
        $variationExpression = $variationColumn !== null
            ? 'vld.' . $variationColumn
            : null;

        if ($productColumn !== null) {
            $productExpression = 'vld.' . $productColumn;
            $stockQuery->whereIn($productExpression, $productIds);
        } else {
            // Common host schemas keep product ownership on variations rather
            // than duplicating product_id on variation_location_details.
            if (
                $variationColumn === null
                || ! $this->hasTable('variations')
                || ! $this->hasColumn('variations', 'id')
                || ! $this->hasColumn('variations', 'product_id')
            ) {
                return $products;
            }

            $stockQuery->join('variations as sv', 'sv.id', '=', 'vld.' . $variationColumn)
                ->whereIn('sv.product_id', $productIds);
            $productExpression = 'sv.product_id';

            if ($this->hasColumn('variations', 'deleted_at')) {
                $stockQuery->whereNull('sv.deleted_at');
            }
        }

        // No location selected yet = the same product-level total used by the
        // Product New lookup/list. Once selected, use that location only.
        if ($locationId !== null) {
            $stockQuery->where('vld.' . $locationColumn, $locationId);
        }

        if ($this->hasColumn($table, 'deleted_at')) {
            $stockQuery->whereNull('vld.deleted_at');
        }

        $stockQuery->select([
            DB::raw($productExpression . ' as product_id'),
            $variationExpression !== null
                ? DB::raw($variationExpression . ' as variation_id')
                : DB::raw('NULL as variation_id'),
            DB::raw('SUM(COALESCE(vld.' . $quantityColumn . ', 0)) as system_qty'),
        ])->groupByRaw($productExpression);

        if ($variationExpression !== null) {
            $stockQuery->groupByRaw($variationExpression);
        }

        $stockByVariation = [];
        $stockByProduct = [];

        foreach ($stockQuery->get() as $stock) {
            $productId = (int) $stock->product_id;
            $variationId = $stock->variation_id !== null ? (int) $stock->variation_id : null;
            $quantity = (float) $stock->system_qty;

            $stockByProduct[$productId] = ($stockByProduct[$productId] ?? 0) + $quantity;
            if ($variationId !== null) {
                $stockByVariation[$productId . ':' . $variationId] = $quantity;
            }
        }

        // Match Products New Stock Centre's effective variation-key rule.
        // Missing/NULL type is treated as single there, so do the same here.
        $productTypes = [];
        if (
            $this->hasTable('products')
            && $this->hasColumn('products', 'id')
            && $this->hasColumn('products', 'type')
        ) {
            $productTypes = DB::table('products')
                ->whereIn('id', $productIds)
                ->pluck('type', 'id')
                ->mapWithKeys(static fn ($type, $id): array => [
                    (int) $id => strtolower(trim((string) ($type ?? 'single'))),
                ])
                ->all();
        }

        foreach ($products as &$product) {
            $productId = (int) $product['product_id'];
            $variationId = $product['variation_id'] !== null ? (int) $product['variation_id'] : null;
            $variationKey = $productId . ':' . ($variationId ?? 0);
            $productType = $productTypes[$productId] ?? 'single';
            $isSingle = $productType === '' || $productType === 'single';

            $product['system_qty'] = (! $isSingle && $variationId !== null)
                ? (float) ($stockByVariation[$variationKey] ?? 0)
                : (float) ($stockByProduct[$productId] ?? 0);
        }
        unset($product);

        return $products;
    }

    private function applyActiveScope(Builder $query, string $table, string $alias): void
    {
        if ($this->hasColumn($table, 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }

        if ($this->hasColumn($table, 'is_inactive')) {
            $query->where(function (Builder $active) use ($alias): void {
                $active->whereNull($alias . '.is_inactive')
                    ->orWhere($alias . '.is_inactive', 0);
            });
        } elseif ($this->hasColumn($table, 'is_active')) {
            $query->where(function (Builder $active) use ($alias): void {
                $active->whereNull($alias . '.is_active')
                    ->orWhere($alias . '.is_active', 1);
            });
        }
    }

    private function cleanVariationName(string $value): string
    {
        $value = trim($value);

        return in_array(strtoupper($value), ['', 'DUMMY', 'DEFAULT'], true) ? '' : $value;
    }

    private function firstColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($this->hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function hasTable(string $table): bool
    {
        if (! array_key_exists($table, $this->tableCache)) {
            try {
                $this->tableCache[$table] = Schema::hasTable($table);
            } catch (\Throwable $exception) {
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
            } catch (\Throwable $exception) {
                $this->columnCache[$key] = false;
            }
        }

        return $this->columnCache[$key];
    }
}
