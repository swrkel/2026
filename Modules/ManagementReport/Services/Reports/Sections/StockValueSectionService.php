<?php

namespace Modules\ManagementReport\Services\Reports\Sections;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;
use Modules\ManagementReport\Support\TenantConnection;

class StockValueSectionService extends BaseSectionService
{
    public function key()
    {
        return 'stock_value';
    }

    public function build(ReportContext $context)
    {
        $states = $this->currentStockStates($context);
        if ($states === null) {
            return [
                'rows' => [],
                'total' => 0.0,
                'opening_stock' => 0.0,
                'purchased_value' => 0.0,
                'sales_return' => 0.0,
                'stock_adjustment' => 0.0,
                'sold_value' => 0.0,
                'purchase_return' => 0.0,
                'balance_stock' => 0.0,
                'as_of_date' => $context->endDate->toDateString(),
                'basis' => 'unavailable',
            ];
        }

        $variationIds = array_values(array_unique(array_map(function ($state) {
            return (int) $state['variation_id'];
        }, array_values($states))));

        // Preserve the proven historical closing-stock valuation already used by
        // Management Report.  The new status rows explain how that closing value
        // moves during the selected period without changing downstream reports
        // that depend on stock_value.payload.total.
        $movements = $this->postPeriodMovements($context, $variationIds);
        $costs = $this->historicalCosts($context, $variationIds);
        $grouped = [];

        foreach ($states as $key => $state) {
            $quantity = (float) $state['current_qty'] - (float) ($movements[$key] ?? 0.0);
            if (abs($quantity) < 0.000001) {
                continue;
            }

            $locationCostKey = ((int) $state['location_id']) . ':' . ((int) $state['variation_id']);
            $unitCost = $costs['location'][$locationCostKey]
                ?? $costs['variation'][(int) $state['variation_id']]
                ?? (float) $state['fallback_cost'];

            $groupKey = $context->storeId
                ? 'store:' . (int) $state['store_id']
                : 'location:' . (int) $state['location_id'];

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'label' => $state['label'],
                    'amount' => 0.0,
                    'quantity' => 0.0,
                ];
            }

            $grouped[$groupKey]['quantity'] += $quantity;
            $grouped[$groupKey]['amount'] += $quantity * $unitCost;
        }

        $balanceStock = $this->amount(array_sum(array_column($grouped, 'amount')));
        $purchasedValue = $this->periodPurchasedValue($context);
        $salesReturn = $this->periodSalesReturnValue($context);
        $stockAdjustment = $this->periodStockAdjustmentValue($context);
        $soldValue = $this->periodSoldValue($context);
        $purchaseReturn = $this->periodPurchaseReturnValue($context);

        // Required formula:
        // Balance Stock = Opening Stock + Purchased Value + Sales Return
        //               + Stock Adjustment - Sold Value - Purchase Return
        // Opening Stock is therefore the brought-forward value that reconciles
        // the selected period's movements to the historically valued closing stock.
        $openingStock = $this->amount(
            $balanceStock
            - $purchasedValue
            - $salesReturn
            - $stockAdjustment
            + $soldValue
            + $purchaseReturn
        );

        $rows = [
            ['label' => 'Opening Stock', 'amount' => $openingStock],
            ['label' => 'Purchased Value', 'amount' => $purchasedValue],
            ['label' => 'Sales Return', 'amount' => $salesReturn],
            ['label' => 'Stock Adjustment', 'amount' => $stockAdjustment],
            ['label' => 'Sold', 'amount' => $soldValue],
            ['label' => 'Purchase Return', 'amount' => $purchaseReturn],
        ];

        return [
            'rows' => $rows,
            'total' => $balanceStock,
            'opening_stock' => $openingStock,
            'purchased_value' => $purchasedValue,
            'sales_return' => $salesReturn,
            'stock_adjustment' => $stockAdjustment,
            'sold_value' => $soldValue,
            'purchase_return' => $purchaseReturn,
            'balance_stock' => $balanceStock,
            'as_of_date' => $context->endDate->toDateString(),
            'basis' => 'opening_plus_period_stock_movements',
        ];
    }

    protected function periodPurchasedValue(ReportContext $context)
    {
        if (!$this->schema->table('transactions') || !$this->schema->table('purchase_lines')) {
            return 0.0;
        }

        $quantity = $this->schema->firstColumn('purchase_lines', ['quantity']);
        $cost = $this->schema->firstColumn('purchase_lines', ['purchase_price_inc_tax', 'purchase_price', 'pp_without_discount']);
        if (!$quantity || !$cost) {
            return $this->transactionSum($context, ['purchase', 'purchase_transfer', 'production_purchase'], 'final_total');
        }

        $query = TenantConnection::db()->table('transactions')
            ->join('purchase_lines', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->whereBetween('transactions.transaction_date', [$context->startDate, $context->endDate])
            ->whereIn('transactions.type', ['purchase', 'purchase_transfer', 'production_purchase']);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'purchase_lines');

        return $this->amount((float) $query->sum(DB::raw('purchase_lines.' . $quantity . ' * purchase_lines.' . $cost)));
    }

    protected function periodPurchaseReturnValue(ReportContext $context)
    {
        if (!$this->schema->table('transactions')) {
            return 0.0;
        }

        // Purchase-return document totals are recorded at purchase value and are
        // the safest source across old and new tenant schemas.
        return $this->transactionSum($context, ['purchase_return'], 'final_total');
    }

    protected function periodSoldValue(ReportContext $context)
    {
        if (!$this->schema->table('transactions') || !$this->schema->table('transaction_sell_lines') || !$this->schema->table('variations')) {
            return 0.0;
        }

        $quantity = $this->schema->firstColumn('transaction_sell_lines', ['quantity']);
        $cost = $this->schema->firstColumn('variations', ['dpp_inc_tax', 'default_purchase_price', 'purchase_price']);
        if (!$quantity || !$cost || !$this->schema->column('transaction_sell_lines', 'variation_id')) {
            return 0.0;
        }

        $query = TenantConnection::db()->table('transactions')
            ->join('transaction_sell_lines', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->where('transactions.business_id', $context->businessId)
            ->whereBetween('transactions.transaction_date', [$context->startDate, $context->endDate])
            ->whereIn('transactions.type', ['sell', 'pos', 'sell_transfer', 'production_sell']);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'transaction_sell_lines');

        return $this->amount((float) $query->sum(DB::raw('transaction_sell_lines.' . $quantity . ' * COALESCE(variations.' . $cost . ', 0)')));
    }

    protected function periodSalesReturnValue(ReportContext $context)
    {
        if (!$this->schema->table('transactions') || !$this->schema->table('transaction_sell_lines') || !$this->schema->table('variations') || !$this->schema->column('transactions', 'return_parent_id')) {
            return 0.0;
        }

        $returned = $this->schema->firstColumn('transaction_sell_lines', ['quantity_returned']);
        $cost = $this->schema->firstColumn('variations', ['dpp_inc_tax', 'default_purchase_price', 'purchase_price']);
        if (!$returned || !$cost || !$this->schema->column('transaction_sell_lines', 'variation_id')) {
            return 0.0;
        }

        $query = TenantConnection::db()->table('transactions')
            ->join('transactions as parent_transaction', 'transactions.return_parent_id', '=', 'parent_transaction.id')
            ->join('transaction_sell_lines', 'transaction_sell_lines.transaction_id', '=', 'parent_transaction.id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->where('transactions.business_id', $context->businessId)
            ->whereBetween('transactions.transaction_date', [$context->startDate, $context->endDate])
            ->where('transactions.type', 'sell_return')
            ->where('transaction_sell_lines.' . $returned, '>', 0);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'transaction_sell_lines');

        return $this->amount((float) $query->sum(DB::raw('transaction_sell_lines.' . $returned . ' * COALESCE(variations.' . $cost . ', 0)')));
    }

    protected function periodStockAdjustmentValue(ReportContext $context)
    {
        if (!$this->schema->table('transactions') || !$this->schema->table('stock_adjustment_lines') || !$this->schema->table('variations')) {
            return 0.0;
        }

        $quantity = $this->schema->firstColumn('stock_adjustment_lines', ['quantity']);
        $lineType = $this->schema->firstColumn('stock_adjustment_lines', ['stock_adjustment_type', 'type']);
        $lineCost = $this->schema->firstColumn('stock_adjustment_lines', ['unit_price', 'purchase_price', 'unit_cost']);
        $variationCost = $this->schema->firstColumn('variations', ['dpp_inc_tax', 'default_purchase_price', 'purchase_price']);
        if (!$quantity || !$variationCost || !$this->schema->column('stock_adjustment_lines', 'variation_id')) {
            return 0.0;
        }

        $query = TenantConnection::db()->table('transactions')
            ->join('stock_adjustment_lines', 'stock_adjustment_lines.transaction_id', '=', 'transactions.id')
            ->join('variations', 'stock_adjustment_lines.variation_id', '=', 'variations.id')
            ->where('transactions.business_id', $context->businessId)
            ->whereBetween('transactions.transaction_date', [$context->startDate, $context->endDate])
            ->where('transactions.type', 'stock_adjustment');

        if ($this->schema->column('transactions', 'sub_type')) {
            $query->where(function ($inner) {
                $inner->whereNull('transactions.sub_type')->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            });
        }

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'stock_adjustment_lines');

        $costExpression = $lineCost
            ? 'COALESCE(NULLIF(stock_adjustment_lines.' . $lineCost . ', 0), variations.' . $variationCost . ', 0)'
            : 'COALESCE(variations.' . $variationCost . ', 0)';

        if ($lineType) {
            $signExpression = "CASE WHEN LOWER(COALESCE(stock_adjustment_lines.$lineType, 'decrease')) IN ('increase','increased','add','addition','plus') THEN 1 ELSE -1 END";
        } elseif ($this->schema->column('transactions', 'stock_adjustment_type')) {
            $signExpression = "CASE WHEN LOWER(COALESCE(transactions.stock_adjustment_type, 'decrease')) IN ('increase','increased','add','addition','plus') THEN 1 ELSE -1 END";
        } else {
            // Legacy stock-adjustment records are reductions unless quantity was
            // explicitly stored as a negative value.
            $signExpression = '-1';
        }

        $expression = $signExpression . ' * ABS(stock_adjustment_lines.' . $quantity . ') * (' . $costExpression . ')';
        return $this->amount((float) $query->sum(DB::raw($expression)));
    }

    /**
     * Start with the tenant's present stock and reverse every dated movement
     * after the selected report end date. This produces a repeatable stock
     * quantity as at the historical date instead of displaying today's stock.
     */
    protected function currentStockStates(ReportContext $context)
    {
        $useStoreStock = $context->storeId && $this->schema->table('variation_store_details');
        $table = $useStoreStock ? 'variation_store_details' : 'variation_location_details';

        if (!$this->schema->table($table) || !$this->schema->table('variations') || !$this->schema->table('products')) {
            return null;
        }

        $quantityColumn = $this->schema->firstColumn($table, ['qty_available', 'stock_qty', 'quantity']);
        $variationColumn = $this->schema->firstColumn($table, ['variation_id']);
        $fallbackCostColumn = $this->schema->firstColumn('variations', ['dpp_inc_tax', 'default_purchase_price', 'purchase_price']);
        if (!$quantityColumn || !$variationColumn || !$fallbackCostColumn) {
            return null;
        }

        $query = TenantConnection::db()->table($table . ' as stock')
            ->join('variations', 'stock.' . $variationColumn, '=', 'variations.id')
            ->join('products', 'variations.product_id', '=', 'products.id')
            ->where('products.business_id', $context->businessId);

        if ($this->schema->column('products', 'deleted_at')) {
            $query->whereNull('products.deleted_at');
        }
        if ($this->schema->column($table, 'deleted_at')) {
            $query->whereNull('stock.deleted_at');
        }

        $locationExpression = DB::raw((string) ((int) ($context->locationId ?: 0)) . ' AS location_id');
        if ($this->schema->column($table, 'location_id')) {
            $locationExpression = DB::raw('stock.location_id AS location_id');
            if ($context->locationId) {
                $query->where('stock.location_id', $context->locationId);
            }
        } elseif ($useStoreStock && $this->schema->table('stores') && $this->schema->column('stores', 'location_id')) {
            $query->join('stores', 'stock.store_id', '=', 'stores.id');
            $locationExpression = DB::raw('stores.location_id AS location_id');
            if ($context->locationId) {
                $query->where('stores.location_id', $context->locationId);
            }
        }

        $storeExpression = DB::raw('0 AS store_id');
        if ($this->schema->column($table, 'store_id')) {
            $storeExpression = DB::raw('stock.store_id AS store_id');
            if ($context->storeId) {
                $query->where('stock.store_id', $context->storeId);
            }
        }

        $rows = $query->select([
            DB::raw('stock.' . $variationColumn . ' AS variation_id'),
            $locationExpression,
            $storeExpression,
            DB::raw('stock.' . $quantityColumn . ' AS current_qty'),
            DB::raw('variations.' . $fallbackCostColumn . ' AS fallback_cost'),
        ])->get();

        $locationNames = $this->namesById('business_locations', 'name', $rows->pluck('location_id')->all());
        $storeNames = $this->namesById('stores', 'name', $rows->pluck('store_id')->all());
        $states = [];

        foreach ($rows as $row) {
            $locationId = $this->normaliseLocationId($row->location_id ?? 0, $context);
            $storeId = $this->normaliseStoreId($row->store_id ?? 0, $context);
            $variationId = (int) $row->variation_id;
            $key = $this->movementKey($locationId, $storeId, $variationId, $context);
            $label = $context->storeId
                ? ($storeNames[$storeId] ?? ('Store ' . ($storeId ?: 'All')))
                : ($locationNames[$locationId] ?? ($context->locationId ? 'Selected Location' : 'All Locations'));

            if (!isset($states[$key])) {
                $states[$key] = [
                    'variation_id' => $variationId,
                    'location_id' => $locationId,
                    'store_id' => $storeId,
                    'current_qty' => 0.0,
                    'fallback_cost' => (float) $row->fallback_cost,
                    'label' => $label,
                ];
            }
            $states[$key]['current_qty'] += (float) $row->current_qty;
        }

        return $states;
    }

    protected function postPeriodMovements(ReportContext $context, array $variationIds)
    {
        $movements = [];
        if (!$variationIds || !$this->schema->table('transactions')) {
            return $movements;
        }

        $this->appendPurchaseMovements($movements, $context, $variationIds);
        $this->appendPurchaseReturnMovements($movements, $context, $variationIds);
        $this->appendSellMovements($movements, $context, $variationIds);
        $this->appendSellReturnMovements($movements, $context, $variationIds);
        $this->appendAdjustmentMovements($movements, $context, $variationIds);

        return $movements;
    }

    protected function appendPurchaseMovements(array &$movements, ReportContext $context, array $variationIds)
    {
        if (!$this->schema->table('purchase_lines')) {
            return;
        }
        $quantity = $this->schema->firstColumn('purchase_lines', ['quantity']);
        if (!$quantity || !$this->schema->column('purchase_lines', 'variation_id')) {
            return;
        }

        $locationSelect = $this->schema->column('transactions', 'location_id')
            ? DB::raw('transactions.location_id AS location_id')
            : DB::raw(((int) ($context->locationId ?: 0)) . ' AS location_id');
        $storeSelect = $this->schema->column('transactions', 'store_id')
            ? DB::raw('transactions.store_id AS store_id')
            : DB::raw(((int) ($context->storeId ?: 0)) . ' AS store_id');

        $query = TenantConnection::db()->table('transactions')
            ->join('purchase_lines', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->where('transactions.transaction_date', '>', $context->endDate)
            ->whereIn('transactions.type', [
                'purchase', 'opening_stock', 'purchase_transfer', 'production_purchase',
                '_deleted_purchase',
            ])
            ->whereIn('purchase_lines.variation_id', $variationIds);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'purchase_lines');

        $query->select([
            $locationSelect,
            $storeSelect,
            DB::raw('purchase_lines.variation_id AS variation_id'),
            DB::raw("SUM(CASE WHEN transactions.type IN ('purchase','opening_stock','purchase_transfer','production_purchase') THEN purchase_lines.$quantity WHEN transactions.type = '_deleted_purchase' THEN -purchase_lines.$quantity ELSE 0 END) AS movement"),
        ]);

        $this->groupMovementQuery($query, 'purchase_lines');
        $this->mergeMovementRows($movements, $query->get(), $context);
    }

    protected function appendPurchaseReturnMovements(array &$movements, ReportContext $context, array $variationIds)
    {
        if (!$this->schema->table('purchase_lines') || !$this->schema->column('transactions', 'return_parent_id')) {
            return;
        }
        $returned = $this->schema->firstColumn('purchase_lines', ['quantity_returned']);
        if (!$returned || !$this->schema->column('purchase_lines', 'variation_id')) {
            return;
        }

        $locationSelect = $this->schema->column('transactions', 'location_id')
            ? DB::raw('transactions.location_id AS location_id')
            : DB::raw(((int) ($context->locationId ?: 0)) . ' AS location_id');
        $storeSelect = $this->schema->column('transactions', 'store_id')
            ? DB::raw('transactions.store_id AS store_id')
            : DB::raw(((int) ($context->storeId ?: 0)) . ' AS store_id');

        $query = TenantConnection::db()->table('transactions')
            ->join('transactions as parent_transaction', 'transactions.return_parent_id', '=', 'parent_transaction.id')
            ->join('purchase_lines', 'purchase_lines.transaction_id', '=', 'parent_transaction.id')
            ->where('transactions.business_id', $context->businessId)
            ->where('transactions.type', 'purchase_return')
            ->where('transactions.transaction_date', '>', $context->endDate)
            ->whereIn('purchase_lines.variation_id', $variationIds)
            ->where('purchase_lines.' . $returned, '>', 0);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'purchase_lines');

        $query->select([
            $locationSelect,
            $storeSelect,
            DB::raw('purchase_lines.variation_id AS variation_id'),
            DB::raw('SUM(-purchase_lines.' . $returned . ') AS movement'),
        ]);

        $this->groupMovementQuery($query, 'purchase_lines');
        $this->mergeMovementRows($movements, $query->get(), $context);
    }

    protected function appendSellMovements(array &$movements, ReportContext $context, array $variationIds)
    {
        if (!$this->schema->table('transaction_sell_lines')) {
            return;
        }
        $quantity = $this->schema->firstColumn('transaction_sell_lines', ['quantity']);
        if (!$quantity || !$this->schema->column('transaction_sell_lines', 'variation_id')) {
            return;
        }

        $locationSelect = $this->schema->column('transactions', 'location_id')
            ? DB::raw('transactions.location_id AS location_id')
            : DB::raw(((int) ($context->locationId ?: 0)) . ' AS location_id');
        $storeSelect = $this->schema->column('transactions', 'store_id')
            ? DB::raw('transactions.store_id AS store_id')
            : DB::raw(((int) ($context->storeId ?: 0)) . ' AS store_id');

        $query = TenantConnection::db()->table('transactions')
            ->join('transaction_sell_lines', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->where('transactions.transaction_date', '>', $context->endDate)
            ->whereIn('transactions.type', ['sell', 'pos', 'sell_transfer', 'production_sell'])
            ->whereIn('transaction_sell_lines.variation_id', $variationIds);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'transaction_sell_lines');

        $query->select([
            $locationSelect,
            $storeSelect,
            DB::raw('transaction_sell_lines.variation_id AS variation_id'),
            DB::raw("SUM(-transaction_sell_lines.$quantity) AS movement"),
        ]);

        $this->groupMovementQuery($query, 'transaction_sell_lines');
        $this->mergeMovementRows($movements, $query->get(), $context);
    }

    protected function appendSellReturnMovements(array &$movements, ReportContext $context, array $variationIds)
    {
        if (!$this->schema->table('transaction_sell_lines') || !$this->schema->column('transactions', 'return_parent_id')) {
            return;
        }
        $returned = $this->schema->firstColumn('transaction_sell_lines', ['quantity_returned']);
        if (!$returned || !$this->schema->column('transaction_sell_lines', 'variation_id')) {
            return;
        }

        $locationSelect = $this->schema->column('transactions', 'location_id')
            ? DB::raw('transactions.location_id AS location_id')
            : DB::raw(((int) ($context->locationId ?: 0)) . ' AS location_id');
        $storeSelect = $this->schema->column('transactions', 'store_id')
            ? DB::raw('transactions.store_id AS store_id')
            : DB::raw(((int) ($context->storeId ?: 0)) . ' AS store_id');

        $query = TenantConnection::db()->table('transactions')
            ->join('transactions as parent_transaction', 'transactions.return_parent_id', '=', 'parent_transaction.id')
            ->join('transaction_sell_lines', 'transaction_sell_lines.transaction_id', '=', 'parent_transaction.id')
            ->where('transactions.business_id', $context->businessId)
            ->where('transactions.type', 'sell_return')
            ->where('transactions.transaction_date', '>', $context->endDate)
            ->whereIn('transaction_sell_lines.variation_id', $variationIds)
            ->where('transaction_sell_lines.' . $returned, '>', 0);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'transaction_sell_lines');

        $query->select([
            $locationSelect,
            $storeSelect,
            DB::raw('transaction_sell_lines.variation_id AS variation_id'),
            DB::raw('SUM(transaction_sell_lines.' . $returned . ') AS movement'),
        ]);

        $this->groupMovementQuery($query, 'transaction_sell_lines');
        $this->mergeMovementRows($movements, $query->get(), $context);
    }

    protected function appendAdjustmentMovements(array &$movements, ReportContext $context, array $variationIds)
    {
        if (!$this->schema->table('stock_adjustment_lines')) {
            return;
        }
        $quantity = $this->schema->firstColumn('stock_adjustment_lines', ['quantity']);
        $type = $this->schema->firstColumn('stock_adjustment_lines', ['stock_adjustment_type', 'type']);
        if (!$quantity || !$type || !$this->schema->column('stock_adjustment_lines', 'variation_id')) {
            return;
        }

        $locationSelect = $this->schema->column('transactions', 'location_id')
            ? DB::raw('transactions.location_id AS location_id')
            : DB::raw(((int) ($context->locationId ?: 0)) . ' AS location_id');
        $storeSelect = $this->schema->column('transactions', 'store_id')
            ? DB::raw('transactions.store_id AS store_id')
            : DB::raw(((int) ($context->storeId ?: 0)) . ' AS store_id');

        $query = TenantConnection::db()->table('transactions')
            ->join('stock_adjustment_lines', 'stock_adjustment_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->where('transactions.type', 'stock_adjustment')
            ->where('transactions.transaction_date', '>', $context->endDate)
            ->whereIn('stock_adjustment_lines.variation_id', $variationIds);

        if ($this->schema->column('transactions', 'sub_type')) {
            $query->where(function ($inner) {
                $inner->whereNull('transactions.sub_type')->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            });
        }

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'stock_adjustment_lines');

        $query->select([
            $locationSelect,
            $storeSelect,
            DB::raw('stock_adjustment_lines.variation_id AS variation_id'),
            DB::raw("SUM(CASE WHEN LOWER(COALESCE(stock_adjustment_lines.$type, 'decrease')) = 'increase' THEN stock_adjustment_lines.$quantity ELSE -stock_adjustment_lines.$quantity END) AS movement"),
        ]);

        $this->groupMovementQuery($query, 'stock_adjustment_lines');
        $this->mergeMovementRows($movements, $query->get(), $context);
    }

    protected function historicalCosts(ReportContext $context, array $variationIds)
    {
        $costs = ['location' => [], 'variation' => []];
        if (!$variationIds || !$this->schema->table('purchase_lines') || !$this->schema->table('transactions')) {
            return $costs;
        }

        $cost = $this->schema->firstColumn('purchase_lines', ['purchase_price_inc_tax', 'purchase_price', 'pp_without_discount']);
        if (!$cost || !$this->schema->column('purchase_lines', 'variation_id')) {
            return $costs;
        }

        $locationSelect = $this->schema->column('transactions', 'location_id')
            ? DB::raw('transactions.location_id AS location_id')
            : DB::raw(((int) ($context->locationId ?: 0)) . ' AS location_id');

        $query = TenantConnection::db()->table('purchase_lines')
            ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $context->businessId)
            ->where('transactions.transaction_date', '<=', $context->endDate)
            ->whereIn('transactions.type', ['purchase', 'opening_stock', 'purchase_transfer', 'production_purchase'])
            ->whereIn('purchase_lines.variation_id', $variationIds)
            ->where('purchase_lines.' . $cost, '>', 0);

        $this->applyTransactionScope($query, $context);
        $this->applyTransactionAndLineIntegrity($query, 'purchase_lines');

        $query->select([
            DB::raw('purchase_lines.variation_id AS variation_id'),
            $locationSelect,
            DB::raw("CAST(SUBSTRING_INDEX(GROUP_CONCAT(purchase_lines.$cost ORDER BY transactions.transaction_date DESC, purchase_lines.id DESC SEPARATOR ','), ',', 1) AS DECIMAL(30,8)) AS unit_cost"),
        ])->groupBy('purchase_lines.variation_id');

        if ($this->schema->column('transactions', 'location_id')) {
            $query->groupBy('transactions.location_id');
        }

        foreach ($query->get() as $row) {
            $variationId = (int) $row->variation_id;
            $locationId = $this->normaliseLocationId($row->location_id ?? 0, $context);
            $locationKey = $locationId . ':' . $variationId;
            $costs['location'][$locationKey] = (float) $row->unit_cost;
            if (!isset($costs['variation'][$variationId])) {
                $costs['variation'][$variationId] = (float) $row->unit_cost;
            }
        }

        return $costs;
    }

    protected function applyTransactionScope($query, ReportContext $context)
    {
        if ($context->locationId && $this->schema->column('transactions', 'location_id')) {
            $query->where('transactions.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('transactions', 'store_id')) {
            $query->where('transactions.store_id', $context->storeId);
        }
        if ($context->shiftId && $this->schema->column('transactions', 'shift_id')) {
            $query->where('transactions.shift_id', $context->shiftId);
        }
    }

    protected function applyTransactionAndLineIntegrity($query, $lineTable)
    {
        if ($this->schema->column('transactions', 'status')) {
            $query->whereIn('transactions.status', ['final', 'received']);
        }
        if ($this->schema->column('transactions', 'deleted_at')) {
            $query->whereNull('transactions.deleted_at');
        }
        if ($this->schema->column($lineTable, 'deleted_at')) {
            $query->whereNull($lineTable . '.deleted_at');
        }
    }

    protected function groupMovementQuery($query, $lineTable)
    {
        if ($this->schema->column('transactions', 'location_id')) {
            $query->groupBy('transactions.location_id');
        }
        if ($this->schema->column('transactions', 'store_id')) {
            $query->groupBy('transactions.store_id');
        }
        $query->groupBy($lineTable . '.variation_id');
    }

    protected function mergeMovementRows(array &$movements, $rows, ReportContext $context)
    {
        foreach ($rows as $row) {
            $locationId = $this->normaliseLocationId($row->location_id ?? 0, $context);
            $storeId = $this->normaliseStoreId($row->store_id ?? 0, $context);
            $key = $this->movementKey($locationId, $storeId, (int) $row->variation_id, $context);
            $movements[$key] = ($movements[$key] ?? 0.0) + (float) $row->movement;
        }
    }

    protected function movementKey($locationId, $storeId, $variationId, ReportContext $context)
    {
        return ((int) $locationId) . ':' . ($context->storeId ? (int) $storeId : 0) . ':' . ((int) $variationId);
    }

    protected function normaliseLocationId($locationId, ReportContext $context)
    {
        $locationId = (int) $locationId;
        return $locationId > 0 ? $locationId : (int) ($context->locationId ?: 0);
    }

    protected function normaliseStoreId($storeId, ReportContext $context)
    {
        $storeId = (int) $storeId;
        return $storeId > 0 ? $storeId : (int) ($context->storeId ?: 0);
    }

    protected function namesById($table, $nameColumn, array $ids)
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids))));
        if (!$ids || !$this->schema->table($table) || !$this->schema->column($table, $nameColumn)) {
            return [];
        }

        return TenantConnection::db()->table($table)->whereIn('id', $ids)->pluck($nameColumn, 'id')->all();
    }
}
