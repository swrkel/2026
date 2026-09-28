<?php

namespace Modules\ManagementReport\Services\Reports\Sections;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;
use Modules\ManagementReport\Support\TenantConnection;

class SalesSectionService extends BaseSectionService
{
    public function key()
    {
        return 'sales';
    }

    public function build(ReportContext $context)
    {
        /*
         * IS2219: resolve the saved settlement sources FIRST.  Earlier code
         * decided to suppress the Finance-generated SW sell transaction merely
         * because the sw_* schema existed.  If the direct SW reader returned no
         * rows for any reason, the generated copy had already been excluded and
         * the settlement vanished from this report.  We now suppress a core copy
         * only when we actually have authoritative saved rows to replace it.
         */
        $swSourceRows = $this->canBuildSwSettlementSales()
            ? $this->swSettlementSalesRows($context)
            : [];
        $standaloneSwSourceRows = $this->canBuildStandaloneSwSettlementSales()
            ? $this->standaloneSwSettlementSalesRows($context)
            : [];

        $rows = [];

        if ($this->canBuildSubCategorySales()) {
            $quantityExpression = $this->quantityExpression();
            $unitPriceExpression = $this->inclusiveUnitPriceExpression();
            $discountBasePriceExpression = $this->discountBasePriceExpression();
            $discountExpression = $this->discountExpression(
                $quantityExpression,
                $discountBasePriceExpression
            );
            $lineTotalExpression = $this->lineTotalExpression(
                $quantityExpression,
                $unitPriceExpression
            );

            $query = TenantConnection::db()->table('transactions as t')
                ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'tsl.product_id', '=', 'p.id');

            $hasCategories = $this->schema->table('categories')
                && $this->schema->column('products', 'sub_category_id')
                && $this->schema->column('categories', 'id')
                && $this->schema->column('categories', 'name');

            if ($hasCategories) {
                $query->leftJoin('categories as sc', 'p.sub_category_id', '=', 'sc.id')
                    ->selectRaw("COALESCE(NULLIF(TRIM(sc.name), ''), 'Uncategorized') AS sub_category")
                    ->groupBy('p.sub_category_id', 'sc.name');
            } elseif ($this->schema->column('products', 'sub_category_id')) {
                $query->selectRaw("CASE WHEN p.sub_category_id IS NULL OR p.sub_category_id = 0 THEN 'Uncategorized' ELSE CONCAT('Sub Category #', p.sub_category_id) END AS sub_category")
                    ->groupBy('p.sub_category_id');
            } else {
                $query->selectRaw("'Uncategorized' AS sub_category");
            }

            $query->selectRaw("SUM({$quantityExpression}) AS sold_qty")
                ->selectRaw(
                    "CASE WHEN ABS(SUM({$quantityExpression})) < 0.0000001 THEN 0 " .
                    "ELSE SUM(({$quantityExpression}) * ({$unitPriceExpression})) / SUM({$quantityExpression}) END AS unit_price"
                )
                ->selectRaw("SUM({$discountExpression}) AS discount")
                ->selectRaw("SUM({$lineTotalExpression}) AS total");

            $this->applyTransactionScope($query, $context);

            if ($swSourceRows) {
                $this->excludeSwSettlementTransactions($query);
            }
            if ($standaloneSwSourceRows) {
                $this->excludeStandaloneSwSettlementTransactions($query);
            }
            $this->applyLineSafetyFilters($query);

            $rows = $query
                ->havingRaw("ABS(SUM({$quantityExpression})) > 0.0000001 OR ABS(SUM({$lineTotalExpression})) > 0.0000001")
                ->orderBy('sub_category')
                ->get()
                ->map(function ($row) {
                    return [
                        'sub_category' => (string) ($row->sub_category ?: 'Uncategorized'),
                        'sold_qty' => $this->amount($row->sold_qty),
                        'unit_price' => $this->amount($row->unit_price),
                        'discount' => $this->amount($row->discount),
                        'total' => $this->amount($row->total),
                    ];
                })
                ->values()
                ->all();
        }

        if ($swSourceRows) {
            $rows = $this->mergeSubCategoryRows($rows, $swSourceRows);
        }
        if ($standaloneSwSourceRows) {
            $rows = $this->mergeSubCategoryRows($rows, $standaloneSwSourceRows);
        }

        return $this->payloadFromSubCategoryRows($rows);
    }

    /** Build summary figures from already-classified sub-category rows. */
    protected function payloadFromSubCategoryRows(array $rows)
    {
        $soldQty = $this->amount(array_sum(array_column($rows, 'sold_qty')));
        $discount = $this->amount(array_sum(array_column($rows, 'discount')));
        $grandTotal = $this->amount(array_sum(array_column($rows, 'total')));
        $weightedUnitValue = 0.0;

        foreach ($rows as $row) {
            $weightedUnitValue += (float) $row['sold_qty'] * (float) $row['unit_price'];
        }

        $averageUnitPrice = abs($soldQty) > 0.0000001
            ? $this->amount($weightedUnitValue / $soldQty)
            : 0.0;

        return [
            'summary' => [
                'sold_qty' => $soldQty,
                'average_unit_price' => $averageUnitPrice,
                'total_discount' => $discount,
                'grand_total' => $grandTotal,
                'gross_sales' => $grandTotal,
                'sales_returns' => 0.0,
                'net_sales' => $grandTotal,
            ],
            'rows' => $rows,
            'total' => $grandTotal,
        ];
    }


    /**
     * New standalone SW schema introduced in the SW module.  A settlement is
     * reportable only after status = 2 (settled).
     */
    protected function canBuildStandaloneSwSettlementSales()
    {
        if (!$this->schema->table('sw_settlements') || !$this->schema->table('products')) return false;
        if (!$this->schema->column('sw_settlements', 'id')
            || !$this->schema->column('sw_settlements', 'business_id')
            || !$this->schema->column('sw_settlements', 'status')
            || !$this->schema->firstColumn('sw_settlements', ['transaction_date', 'date', 'created_at'])) {
            return false;
        }

        foreach (['sw_settlement_lines', 'sw_other_sales', 'sw_other_income'] as $table) {
            if (!$this->schema->table($table)) continue;
            if (!$this->schema->column($table, 'settlement_id')) continue;
            $hasProduct = $this->schema->column($table, 'product_id');
            // Meter lines can be resolved from their pump when an older row
            // did not persist product_id. The current SW Finance poster uses
            // this same fallback (pump.product_id, then fuel tank product).
            if (!$hasProduct && !($table === 'sw_settlement_lines' && $this->schema->column($table, 'pump_id'))) continue;
            if (!$this->schema->firstColumn($table, ['quantity', 'qty', 'sold_qty'])) continue;
            if ($this->schema->firstColumn($table, ['amount', 'sub_total', 'total_amount'])) return true;
        }

        // Product-level credit sales are optional on older SW schemas, but if
        // present they are also a valid standalone product sales source.
        if ($this->schema->table('sw_settlement_credit_sales')
            && $this->schema->column('sw_settlement_credit_sales', 'settlement_id')) {
            if ($this->schema->column('sw_settlement_credit_sales', 'product_id')
                && $this->schema->firstColumn('sw_settlement_credit_sales', ['quantity', 'qty'])
                && $this->schema->firstColumn('sw_settlement_credit_sales', ['amount', 'sub_total'])) {
                return true;
            }

            if ($this->schema->column('sw_settlement_credit_sales', 'daily_credit_sale_id')
                && $this->schema->table('sw_daily_credit_sale_lines')
                && $this->schema->column('sw_daily_credit_sale_lines', 'sw_daily_credit_sale_id')
                && $this->schema->column('sw_daily_credit_sale_lines', 'product_id')
                && $this->schema->firstColumn('sw_daily_credit_sale_lines', ['quantity', 'qty'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * SW IS2211 also posts settled SW sales to normal transaction_sell_lines so
     * Finance can read them.  Management Report reads the authoritative sw_*
     * rows below, therefore suppress those generated core copies to avoid
     * counting a settlement twice.
     */
    protected function excludeStandaloneSwSettlementTransactions($query)
    {
        if (!$this->schema->column('transactions', 'invoice_no')
            || !$this->schema->column('sw_settlements', 'settlement_no')) {
            return;
        }

        $hasDeletedAt = $this->schema->column('sw_settlements', 'deleted_at');

        $query->whereNotExists(function ($sw) use ($hasDeletedAt) {
            $sw->selectRaw('1')
                ->from('sw_settlements as mgmt_sw_settlement')
                ->whereColumn('mgmt_sw_settlement.business_id', 't.business_id')
                ->whereColumn('mgmt_sw_settlement.settlement_no', 't.invoice_no')
                ->where('mgmt_sw_settlement.status', 2);

            if ($hasDeletedAt) {
                $sw->whereNull('mgmt_sw_settlement.deleted_at');
            }
        });
    }

    protected function standaloneSwSettlementSalesRows(ReportContext $context)
    {
        $dateColumn = $this->schema->firstColumn('sw_settlements', ['transaction_date', 'date', 'created_at']);
        if (!$dateColumn) return [];

        $settlementQuery = TenantConnection::db()->table('sw_settlements')
            ->where('sw_settlements.business_id', $context->businessId)
            ->where('sw_settlements.status', 2)
            ->whereDate('sw_settlements.' . $dateColumn, '>=', $context->startDate)
            ->whereDate('sw_settlements.' . $dateColumn, '<=', $context->endDate);

        if ($this->schema->column('sw_settlements', 'deleted_at')) {
            $settlementQuery->whereNull('sw_settlements.deleted_at');
        }
        if ($context->locationId && $this->schema->column('sw_settlements', 'location_id')) {
            $settlementQuery->where('sw_settlements.location_id', $context->locationId);
        }

        $settlementIds = $settlementQuery->pluck('sw_settlements.id')
            ->map(function ($id) { return (int) $id; })
            ->filter()
            ->values()
            ->all();

        if (!$settlementIds) return [];

        $rows = [];
        foreach (['sw_settlement_lines', 'sw_other_sales', 'sw_other_income'] as $table) {
            $sourceRows = $table === 'sw_settlement_lines'
                ? $this->standaloneSwMeterLineRows($settlementIds, $context)
                : $this->standaloneSwSourceTableRows($table, $settlementIds, $context);
            if ($sourceRows) $rows = array_merge($rows, $sourceRows);
        }

        $creditRows = $this->standaloneSwCreditSaleRows($settlementIds, $context);
        if ($creditRows) $rows = array_merge($rows, $creditRows);

        return $this->mergeSubCategoryRows([], $rows);
    }

    /**
     * Meter lines occasionally reach an older tenant with product_id blank even
     * though pump_id is correct.  Resolve the product exactly as the current SW
     * Finance posting service does: saved line product -> pump product -> fuel
     * tank product.  This prevents valid SW fuel sales disappearing from the
     * Product Sub Category section.
     */
    protected function standaloneSwMeterLineRows(array $settlementIds, ReportContext $context)
    {
        $table = 'sw_settlement_lines';
        if (!$settlementIds || !$this->schema->table($table)) return [];
        if (!$this->schema->column($table, 'settlement_id')) return [];

        $quantityColumn = $this->schema->firstColumn($table, ['quantity', 'qty', 'sold_qty']);
        $totalColumn = $this->schema->firstColumn($table, ['amount', 'sub_total', 'total_amount']);
        $priceColumn = $this->schema->firstColumn($table, ['rate', 'price', 'unit_price_inc_tax', 'unit_price']);
        if (!$quantityColumn || !$totalColumn) return [];

        $query = TenantConnection::db()->table($table . ' as sws')
            ->whereIn('sws.settlement_id', $settlementIds);

        $productCandidates = [];
        if ($this->schema->column($table, 'product_id')) {
            $productCandidates[] = 'NULLIF(sws.product_id,0)';
        }

        $canUsePump = $this->schema->column($table, 'pump_id')
            && $this->schema->table('pumps')
            && $this->schema->column('pumps', 'id');

        if ($canUsePump) {
            $query->leftJoin('pumps as swp', 'sws.pump_id', '=', 'swp.id');
            if ($this->schema->column('pumps', 'product_id')) {
                $productCandidates[] = 'NULLIF(swp.product_id,0)';
            }
            if ($this->schema->column('pumps', 'fuel_tank_id')
                && $this->schema->table('fuel_tanks')
                && $this->schema->column('fuel_tanks', 'id')
                && $this->schema->column('fuel_tanks', 'product_id')) {
                $query->leftJoin('fuel_tanks as swft', 'swp.fuel_tank_id', '=', 'swft.id');
                $productCandidates[] = 'NULLIF(swft.product_id,0)';
            }
        }

        if (!$productCandidates) return [];
        $productExpression = count($productCandidates) === 1
            ? $productCandidates[0]
            : 'COALESCE(' . implode(',', $productCandidates) . ')';

        $query->join('products as p', 'p.id', '=', DB::raw($productExpression));

        if ($this->schema->column('products', 'business_id')) {
            $query->where('p.business_id', $context->businessId);
        }
        if ($this->schema->column($table, 'deleted_at')) {
            $query->whereNull('sws.deleted_at');
        }
        if ($this->schema->column('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        $this->applyStandaloneSubCategoryGrouping($query);

        $qtyExpression = 'COALESCE(sws.' . $quantityColumn . ',0)';
        $totalExpression = 'COALESCE(sws.' . $totalColumn . ',0)';
        $priceValueExpression = $priceColumn
            ? 'COALESCE(sws.' . $priceColumn . ',0)'
            : '(CASE WHEN ABS(' . $qtyExpression . ') < 0.0000001 THEN 0 ELSE (' . $totalExpression . ') / (' . $qtyExpression . ') END)';
        $discountExpression = $this->schema->column($table, 'amount_before_discount')
            ? 'GREATEST(COALESCE(sws.amount_before_discount,0) - (' . $totalExpression . '), 0)'
            : '0';

        $items = $query
            ->selectRaw('SUM(' . $qtyExpression . ') AS sold_qty')
            ->selectRaw('SUM(' . $qtyExpression . ' * (' . $priceValueExpression . ')) AS weighted_price')
            ->selectRaw('SUM(' . $discountExpression . ') AS discount')
            ->selectRaw('SUM(' . $totalExpression . ') AS total')
            ->havingRaw('ABS(SUM(' . $qtyExpression . ')) > 0.0000001 OR ABS(SUM(' . $totalExpression . ')) > 0.0000001')
            ->get();

        return $this->standaloneRowsFromAggregates($items);
    }

    protected function standaloneSwSourceTableRows($table, array $settlementIds, ReportContext $context)
    {
        if (!$settlementIds || !$this->schema->table($table)) return [];

        $productColumn = $this->schema->firstColumn($table, ['product_id']);
        $quantityColumn = $this->schema->firstColumn($table, ['quantity', 'qty', 'sold_qty']);
        $totalColumn = $this->schema->firstColumn($table, ['amount', 'sub_total', 'total_amount']);
        $priceColumn = $this->schema->firstColumn($table, ['rate', 'price', 'unit_price_inc_tax', 'unit_price']);
        if (!$this->schema->column($table, 'settlement_id')
            || !$productColumn || !$quantityColumn || !$totalColumn) {
            return [];
        }

        $query = TenantConnection::db()->table($table . ' as sws')
            ->join('products as p', 'sws.' . $productColumn, '=', 'p.id')
            ->whereIn('sws.settlement_id', $settlementIds);

        if ($this->schema->column('products', 'business_id')) {
            $query->where('p.business_id', $context->businessId);
        }
        if ($context->storeId && $this->schema->column($table, 'store_id')) {
            $query->where('sws.store_id', $context->storeId);
        }
        if ($this->schema->column($table, 'deleted_at')) {
            $query->whereNull('sws.deleted_at');
        }
        if ($this->schema->column('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        $this->applyStandaloneSubCategoryGrouping($query);

        $qtyExpression = 'COALESCE(sws.' . $quantityColumn . ',0)';
        $totalExpression = 'COALESCE(sws.' . $totalColumn . ',0)';
        $priceValueExpression = $priceColumn
            ? 'COALESCE(sws.' . $priceColumn . ',0)'
            : '(CASE WHEN ABS(' . $qtyExpression . ') < 0.0000001 THEN 0 ELSE (' . $totalExpression . ') / (' . $qtyExpression . ') END)';

        // amount_before_discount is the safest representation because
        // discount_value may be either a fixed amount or a percentage.
        $discountExpression = $this->schema->column($table, 'amount_before_discount')
            ? 'GREATEST(COALESCE(sws.amount_before_discount,0) - (' . $totalExpression . '), 0)'
            : '0';

        $items = $query
            ->selectRaw('SUM(' . $qtyExpression . ') AS sold_qty')
            ->selectRaw('SUM(' . $qtyExpression . ' * (' . $priceValueExpression . ')) AS weighted_price')
            ->selectRaw('SUM(' . $discountExpression . ') AS discount')
            ->selectRaw('SUM(' . $totalExpression . ') AS total')
            ->havingRaw('ABS(SUM(' . $qtyExpression . ')) > 0.0000001 OR ABS(SUM(' . $totalExpression . ')) > 0.0000001')
            ->get();

        return $this->standaloneRowsFromAggregates($items);
    }

    /**
     * Classify SW credit sales when product detail is available.  Current SW
     * headers can point back to sw_daily_credit_sale_lines; some tenant builds
     * also keep product_id/quantity directly on the settlement credit row.
     */
    protected function standaloneSwCreditSaleRows(array $settlementIds, ReportContext $context)
    {
        if (!$settlementIds || !$this->schema->table('sw_settlement_credit_sales')) return [];
        if (!$this->schema->column('sw_settlement_credit_sales', 'settlement_id')) return [];

        if ($this->schema->column('sw_settlement_credit_sales', 'product_id')
            && $this->schema->firstColumn('sw_settlement_credit_sales', ['quantity', 'qty'])
            && $this->schema->firstColumn('sw_settlement_credit_sales', ['amount', 'sub_total'])) {
            return $this->standaloneSwSourceTableRows('sw_settlement_credit_sales', $settlementIds, $context);
        }

        if (!$this->schema->column('sw_settlement_credit_sales', 'daily_credit_sale_id')
            || !$this->schema->table('sw_daily_credit_sale_lines')
            || !$this->schema->column('sw_daily_credit_sale_lines', 'sw_daily_credit_sale_id')
            || !$this->schema->column('sw_daily_credit_sale_lines', 'product_id')) {
            return [];
        }

        $quantityColumn = $this->schema->firstColumn('sw_daily_credit_sale_lines', ['quantity', 'qty']);
        $totalColumn = $this->schema->firstColumn('sw_daily_credit_sale_lines', ['amount', 'sub_total', 'total_amount']);
        $priceColumn = $this->schema->firstColumn('sw_daily_credit_sale_lines', ['unit_price', 'rate', 'price', 'unit_price_inc_tax']);
        if (!$quantityColumn) return [];

        $query = TenantConnection::db()->table('sw_settlement_credit_sales as swc')
            ->join('sw_daily_credit_sale_lines as swl', 'swl.sw_daily_credit_sale_id', '=', 'swc.daily_credit_sale_id')
            ->join('products as p', 'swl.product_id', '=', 'p.id')
            ->whereIn('swc.settlement_id', $settlementIds);

        if ($this->schema->column('products', 'business_id')) {
            $query->where('p.business_id', $context->businessId);
        }
        if ($this->schema->column('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        $this->applyStandaloneSubCategoryGrouping($query);

        $qtyExpression = 'COALESCE(swl.' . $quantityColumn . ',0)';
        if ($totalColumn) {
            $totalExpression = 'COALESCE(swl.' . $totalColumn . ',0)';
        } elseif ($priceColumn) {
            $totalExpression = '(' . $qtyExpression . ' * COALESCE(swl.' . $priceColumn . ',0))';
        } else {
            return [];
        }

        $priceValueExpression = $priceColumn
            ? 'COALESCE(swl.' . $priceColumn . ',0)'
            : '(CASE WHEN ABS(' . $qtyExpression . ') < 0.0000001 THEN 0 ELSE (' . $totalExpression . ') / (' . $qtyExpression . ') END)';

        $discountColumn = $this->schema->firstColumn('sw_daily_credit_sale_lines', ['unit_discount', 'discount']);
        $discountExpression = $discountColumn
            ? '(' . $qtyExpression . ' * COALESCE(swl.' . $discountColumn . ',0))'
            : '0';

        $items = $query
            ->selectRaw('SUM(' . $qtyExpression . ') AS sold_qty')
            ->selectRaw('SUM(' . $qtyExpression . ' * (' . $priceValueExpression . ')) AS weighted_price')
            ->selectRaw('SUM(' . $discountExpression . ') AS discount')
            ->selectRaw('SUM(' . $totalExpression . ') AS total')
            ->havingRaw('ABS(SUM(' . $qtyExpression . ')) > 0.0000001 OR ABS(SUM(' . $totalExpression . ')) > 0.0000001')
            ->get();

        return $this->standaloneRowsFromAggregates($items);
    }

    protected function applyStandaloneSubCategoryGrouping($query)
    {
        $hasCategories = $this->schema->table('categories')
            && $this->schema->column('products', 'sub_category_id')
            && $this->schema->column('categories', 'id')
            && $this->schema->column('categories', 'name');

        if ($hasCategories) {
            $query->leftJoin('categories as sc', 'p.sub_category_id', '=', 'sc.id')
                ->selectRaw("COALESCE(NULLIF(TRIM(sc.name), ''), 'Uncategorized') AS sub_category")
                ->groupBy('p.sub_category_id', 'sc.name');
        } elseif ($this->schema->column('products', 'sub_category_id')) {
            $query->selectRaw("CASE WHEN p.sub_category_id IS NULL OR p.sub_category_id = 0 THEN 'Uncategorized' ELSE CONCAT('Sub Category #', p.sub_category_id) END AS sub_category")
                ->groupBy('p.sub_category_id');
        } else {
            $query->selectRaw("'Uncategorized' AS sub_category");
        }
    }

    protected function standaloneRowsFromAggregates($items)
    {
        $rows = [];
        foreach ($items as $item) {
            $qty = (float) $item->sold_qty;
            $unitPrice = abs($qty) > 0.0000001 ? ((float) $item->weighted_price / $qty) : 0.0;
            $rows[] = [
                'sub_category' => (string) ($item->sub_category ?: 'Uncategorized'),
                'sold_qty' => $this->amount($qty),
                'unit_price' => $this->amount($unitPrice),
                'discount' => $this->amount($item->discount),
                'total' => $this->amount($item->total),
            ];
        }

        return $rows;
    }

    protected function canBuildSwSettlementSales()
    {
        if (!$this->schema->table('settlements') || !$this->schema->table('products')) return false;
        if (!$this->schema->column('settlements', 'status')) return false;
        if (!$this->schema->firstColumn('settlements', ['settlement_no', 'settlement_number'])) return false;

        foreach (['meter_sales', 'other_sales', 'other_incomes'] as $table) {
            if (!$this->schema->table($table)) continue;
            if (!$this->schema->firstColumn($table, ['settlement_no', 'settlement_number', 'settlement_id'])) continue;
            if (!$this->schema->firstColumn($table, ['product_id'])) continue;
            if (!$this->schema->firstColumn($table, ['qty', 'quantity', 'sold_qty'])) continue;
            if ($this->schema->firstColumn($table, ['sub_total', 'amount', 'total_amount'])) return true;
        }

        return false;
    }

    /**
     * When the direct SW reader is enabled, remove the generated SET-SW sell
     * transaction from the normal transaction_sell_lines path.  Otherwise the
     * same saved settlement would be counted twice on tenants where SW also
     * generated complete sell lines successfully.
     */
    protected function excludeSwSettlementTransactions($query)
    {
        if (!$this->schema->column('transactions', 'invoice_no')) return;

        if ($this->schema->column('transactions', 'is_settlement')) {
            $query->where(function ($q) {
                $q->whereNull('t.is_settlement')
                    ->orWhere('t.is_settlement', '!=', 1)
                    ->orWhereNull('t.invoice_no')
                    ->orWhere('t.invoice_no', 'NOT LIKE', 'SET-SW%');
            });
            return;
        }

        // Old schemas may not have is_settlement.  The SET-SW invoice family is
        // still unique to saved Settlement SW transactions.
        $query->where(function ($q) {
            $q->whereNull('t.invoice_no')->orWhere('t.invoice_no', 'NOT LIKE', 'SET-SW%');
        });
    }

    protected function swSettlementSalesRows(ReportContext $context)
    {
        $numberColumn = $this->schema->firstColumn('settlements', ['settlement_no', 'settlement_number']);
        $dateColumn = $this->schema->firstColumn('settlements', ['transaction_date', 'date', 'created_at']);
        if (!$numberColumn || !$dateColumn) return [];

        $settlementQuery = TenantConnection::db()->table('settlements')
            ->where('settlements.business_id', $context->businessId)
            ->where('settlements.status', 0)
            ->where('settlements.' . $numberColumn, 'LIKE', 'SET-SW%')
            ->whereDate('settlements.' . $dateColumn, '>=', $context->startDate)
            ->whereDate('settlements.' . $dateColumn, '<=', $context->endDate);

        if ($context->locationId && $this->schema->column('settlements', 'location_id')) {
            $settlementQuery->where('settlements.location_id', $context->locationId);
        }
        if ($context->storeId && $this->schema->column('settlements', 'store_id')) {
            $settlementQuery->where('settlements.store_id', $context->storeId);
        }

        $settlements = $settlementQuery->select('settlements.id', 'settlements.' . $numberColumn . ' as settlement_no')->get();
        if ($settlements->isEmpty()) return [];

        $references = [];
        foreach ($settlements as $settlement) {
            $references[] = (string) $settlement->id;
            if (!empty($settlement->settlement_no)) $references[] = (string) $settlement->settlement_no;
        }
        $references = array_values(array_unique($references));

        $rows = [];
        foreach (['meter_sales', 'other_sales', 'other_incomes'] as $table) {
            $sourceRows = $this->swSourceTableRows($table, $references, $context);
            if ($sourceRows) $rows = array_merge($rows, $sourceRows);
        }

        return $this->mergeSubCategoryRows([], $rows);
    }

    protected function swSourceTableRows($table, array $references, ReportContext $context)
    {
        if (!$references || !$this->schema->table($table)) return [];

        $settlementColumn = $this->schema->firstColumn($table, ['settlement_no', 'settlement_number', 'settlement_id']);
        $productColumn = $this->schema->firstColumn($table, ['product_id']);
        $quantityColumn = $this->schema->firstColumn($table, ['qty', 'quantity', 'sold_qty']);
        $totalColumn = $this->schema->firstColumn($table, ['sub_total', 'amount', 'total_amount']);
        $priceColumn = $this->schema->firstColumn($table, ['price', 'unit_price_inc_tax', 'unit_price', 'selling_price']);
        $discountColumn = $this->schema->firstColumn($table, ['discount_amount', 'total_discount', 'discount']);
        if (!$settlementColumn || !$productColumn || !$quantityColumn || !$totalColumn) return [];

        $query = TenantConnection::db()->table($table . ' as sws')
            ->join('products as p', 'sws.' . $productColumn, '=', 'p.id')
            ->whereIn('sws.' . $settlementColumn, $references);

        if ($this->schema->column($table, 'business_id')) {
            $query->where('sws.business_id', $context->businessId);
        }
        if ($this->schema->column('products', 'business_id')) {
            $query->where('p.business_id', $context->businessId);
        }
        if ($context->storeId && $this->schema->column($table, 'store_id')) {
            $query->where('sws.store_id', $context->storeId);
        }
        if ($this->schema->column($table, 'deleted_at')) {
            $query->whereNull('sws.deleted_at');
        }
        if ($this->schema->column('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        $hasCategories = $this->schema->table('categories')
            && $this->schema->column('products', 'sub_category_id')
            && $this->schema->column('categories', 'id')
            && $this->schema->column('categories', 'name');

        if ($hasCategories) {
            $query->leftJoin('categories as sc', 'p.sub_category_id', '=', 'sc.id')
                ->selectRaw("COALESCE(NULLIF(TRIM(sc.name), ''), 'Uncategorized') AS sub_category")
                ->groupBy('p.sub_category_id', 'sc.name');
        } elseif ($this->schema->column('products', 'sub_category_id')) {
            $query->selectRaw("CASE WHEN p.sub_category_id IS NULL OR p.sub_category_id = 0 THEN 'Uncategorized' ELSE CONCAT('Sub Category #', p.sub_category_id) END AS sub_category")
                ->groupBy('p.sub_category_id');
        } else {
            $query->selectRaw("'Uncategorized' AS sub_category");
        }

        $qtyExpression = 'COALESCE(sws.' . $quantityColumn . ',0)';
        $totalExpression = 'COALESCE(sws.' . $totalColumn . ',0)';
        $discountExpression = $discountColumn ? 'COALESCE(sws.' . $discountColumn . ',0)' : '0';
        $priceValueExpression = $priceColumn
            ? 'COALESCE(sws.' . $priceColumn . ',0)'
            : '(CASE WHEN ABS(' . $qtyExpression . ') < 0.0000001 THEN 0 ELSE (' . $totalExpression . ') / (' . $qtyExpression . ') END)';

        $items = $query
            ->selectRaw('SUM(' . $qtyExpression . ') AS sold_qty')
            ->selectRaw('SUM(' . $qtyExpression . ' * (' . $priceValueExpression . ')) AS weighted_price')
            ->selectRaw('SUM(' . $discountExpression . ') AS discount')
            ->selectRaw('SUM(' . $totalExpression . ') AS total')
            ->havingRaw('ABS(SUM(' . $qtyExpression . ')) > 0.0000001 OR ABS(SUM(' . $totalExpression . ')) > 0.0000001')
            ->get();

        $rows = [];
        foreach ($items as $item) {
            $qty = (float) $item->sold_qty;
            $unitPrice = abs($qty) > 0.0000001 ? ((float) $item->weighted_price / $qty) : 0.0;
            $rows[] = [
                'sub_category' => (string) ($item->sub_category ?: 'Uncategorized'),
                'sold_qty' => $this->amount($qty),
                'unit_price' => $this->amount($unitPrice),
                'discount' => $this->amount($item->discount),
                'total' => $this->amount($item->total),
            ];
        }

        return $rows;
    }

    protected function mergeSubCategoryRows(array $baseRows, array $extraRows)
    {
        $grouped = [];
        foreach (array_merge($baseRows, $extraRows) as $row) {
            $label = trim((string) ($row['sub_category'] ?? '')) ?: 'Uncategorized';
            $key = strtolower($label);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'sub_category' => $label,
                    'sold_qty' => 0.0,
                    'unit_price' => 0.0,
                    'discount' => 0.0,
                    'total' => 0.0,
                    '_weighted_price' => 0.0,
                ];
            }

            $qty = (float) ($row['sold_qty'] ?? 0);
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            $grouped[$key]['sold_qty'] += $qty;
            $grouped[$key]['discount'] += (float) ($row['discount'] ?? 0);
            $grouped[$key]['total'] += (float) ($row['total'] ?? 0);
            $grouped[$key]['_weighted_price'] += $qty * $unitPrice;
        }

        $rows = [];
        foreach ($grouped as $row) {
            $qty = (float) $row['sold_qty'];
            $unitPrice = abs($qty) > 0.0000001 ? ((float) $row['_weighted_price'] / $qty) : 0.0;
            unset($row['_weighted_price']);
            $row['sold_qty'] = $this->amount($row['sold_qty']);
            $row['unit_price'] = $this->amount($unitPrice);
            $row['discount'] = $this->amount($row['discount']);
            $row['total'] = $this->amount($row['total']);
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            return strnatcasecmp($a['sub_category'], $b['sub_category']);
        });

        return $rows;
    }

    protected function canBuildSubCategorySales()
    {
        return $this->schema->table('transactions')
            && $this->schema->table('transaction_sell_lines')
            && $this->schema->table('products')
            && $this->schema->column('transactions', 'id')
            && $this->schema->column('transaction_sell_lines', 'transaction_id')
            && $this->schema->column('transaction_sell_lines', 'product_id')
            && $this->schema->column('transaction_sell_lines', 'quantity')
            && $this->schema->column('products', 'id');
    }

    protected function quantityExpression()
    {
        if ($this->schema->column('transaction_sell_lines', 'quantity_returned')) {
            return '(COALESCE(tsl.quantity, 0) - COALESCE(tsl.quantity_returned, 0))';
        }

        return 'COALESCE(tsl.quantity, 0)';
    }

    protected function inclusiveUnitPriceExpression()
    {
        // The invoice/POS/settlement line's actual selling price, inclusive of
        // tax and any line discount already applied by the ERP.
        $column = $this->schema->firstColumn('transaction_sell_lines', [
            'unit_price_inc_tax',
            'unit_price',
        ]);

        return $column ? 'COALESCE(tsl.' . $column . ', 0)' : '0';
    }

    protected function discountBasePriceExpression()
    {
        // Keep discount reporting independent from the displayed tax-inclusive
        // selling price. Percentage discounts are calculated from the original
        // pre-discount price when that column is available.
        $column = $this->schema->firstColumn('transaction_sell_lines', [
            'unit_price_before_discount',
            'unit_price_inc_tax',
            'unit_price',
        ]);

        return $column ? 'COALESCE(tsl.' . $column . ', 0)' : '0';
    }

    protected function discountExpression($quantityExpression, $unitPriceExpression)
    {
        if (!$this->schema->column('transaction_sell_lines', 'line_discount_amount')) {
            return '0';
        }

        $discountAmount = 'COALESCE(tsl.line_discount_amount, 0)';

        if ($this->schema->column('transaction_sell_lines', 'line_discount_type')) {
            return "CASE " .
                "WHEN tsl.line_discount_type = 'percentage' THEN ({$quantityExpression}) * ({$unitPriceExpression}) * ({$discountAmount}) / 100 " .
                "WHEN tsl.line_discount_type = 'fixed' THEN ({$quantityExpression}) * ({$discountAmount}) " .
                "ELSE 0 END";
        }

        return "({$quantityExpression}) * ({$discountAmount})";
    }

    protected function lineTotalExpression($quantityExpression, $unitPriceExpression)
    {
        // unit_price_inc_tax already represents the final line selling price,
        // so subtracting the line discount again would understate the sale.
        return "({$quantityExpression}) * ({$unitPriceExpression})";
    }

    protected function applyTransactionScope($query, ReportContext $context)
    {
        if ($this->schema->column('transactions', 'business_id')) {
            $query->where('t.business_id', $context->businessId);
        }

        if ($this->schema->column('products', 'business_id')) {
            $query->where('p.business_id', $context->businessId);
        }

        if ($context->locationId) {
            if ($this->schema->column('transactions', 'location_id')) {
                $query->where('t.location_id', $context->locationId);
            } elseif ($this->schema->column('transaction_sell_lines', 'location_id')) {
                $query->where('tsl.location_id', $context->locationId);
            }
        }

        if ($context->storeId) {
            if ($this->schema->column('transactions', 'store_id')) {
                $query->where('t.store_id', $context->storeId);
            } elseif ($this->schema->column('transaction_sell_lines', 'store_id')) {
                $query->where('tsl.store_id', $context->storeId);
            }
        }

        if ($context->shiftId) {
            if ($this->schema->column('transactions', 'shift_id')) {
                $query->where('t.shift_id', $context->shiftId);
            } elseif ($this->schema->column('transaction_sell_lines', 'shift_id')) {
                $query->where('tsl.shift_id', $context->shiftId);
            }
        }

        $dateColumn = $this->schema->firstColumn('transactions', [
            'transaction_date',
            'date',
            'created_at',
            'date_and_time',
        ]);

        if ($dateColumn) {
            $query->whereBetween('t.' . $dateColumn, [$context->startDate, $context->endDate]);
        }

        if ($this->schema->column('transactions', 'type')) {
            $query->whereIn('t.type', [
                'sell',
                'pos',
                'fpos_sale',
                'tpos_sale',
                'production_sell',
            ]);
        }

        if ($this->schema->column('transactions', 'status')) {
            $query->where('t.status', 'final');
        }
    }

    protected function applyLineSafetyFilters($query)
    {
        if ($this->schema->column('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }

        if ($this->schema->column('transaction_sell_lines', 'deleted_at')) {
            $query->whereNull('tsl.deleted_at');
        }

        if ($this->schema->column('products', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }

        if ($this->schema->column('transaction_sell_lines', 'parent_sell_line_id')) {
            $query->whereNull('tsl.parent_sell_line_id');
        }
    }

    protected function emptyPayload()
    {
        return [
            'summary' => [
                'sold_qty' => 0.0,
                'average_unit_price' => 0.0,
                'total_discount' => 0.0,
                'grand_total' => 0.0,
                'gross_sales' => 0.0,
                'sales_returns' => 0.0,
                'net_sales' => 0.0,
            ],
            'rows' => [],
            'total' => 0.0,
        ];
    }
}
