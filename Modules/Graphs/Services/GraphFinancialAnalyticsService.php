<?php

namespace Modules\Graphs\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GraphFinancialAnalyticsService
{
    public function currencyPrecision(int $businessId): int
    {
        if (! $businessId || ! Schema::hasTable('business') || ! Schema::hasColumn('business', 'currency_precision')) {
            return 2;
        }

        $value = DB::table('business')->where('id', $businessId)->value('currency_precision');
        return ($value === null || $value === '') ? 2 : max(0, min(6, (int) $value));
    }

    /**
     * Daily reconciliation for finalized petroleum settlements.
     *
     * Fuel sales are read from the normal finalized sell lines so PD, Direct,
     * SW and normal finalized fuel postings use one sales source. Payment
     * figures are read only from finalized settlement-owned payment records;
     * this deliberately avoids counting Pumper Dashboard entries before a
     * settlement is saved/finalized.
     */
    public function reconciliation(
        int $businessId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $locationId = null
    ): array {
        [$start, $end] = $this->normaliseRange($startDate, $endDate);

        $fuelDaily = $this->fuelSalesByDate($businessId, $start, $end, $locationId);
        $creditDaily = $this->fuelCreditSalesByDate($businessId, $start, $end, $locationId);
        $legacy = $this->legacySettlementCollectionsByDate($businessId, $start, $end, $locationId);
        $sw = $this->swSettlementCollectionsByDate($businessId, $start, $end, $locationId);

        $dates = array_unique(array_merge(
            array_keys($fuelDaily),
            array_keys($creditDaily),
            array_keys($legacy['cash']),
            array_keys($legacy['card']),
            array_keys($legacy['bank_deposit']),
            array_keys($sw['cash']),
            array_keys($sw['card']),
            array_keys($sw['bank_deposit'])
        ));
        sort($dates);

        $rows = [];
        foreach ($dates as $date) {
            $fuel = (float) ($fuelDaily[$date] ?? 0);
            $cash = (float) ($legacy['cash'][$date] ?? 0) + (float) ($sw['cash'][$date] ?? 0);
            $card = (float) ($legacy['card'][$date] ?? 0) + (float) ($sw['card'][$date] ?? 0);
            $credit = (float) ($creditDaily[$date] ?? 0);
            $bank = (float) ($legacy['bank_deposit'][$date] ?? 0) + (float) ($sw['bank_deposit'][$date] ?? 0);
            $expectedCash = $fuel - $card - $credit;
            $variation = $cash - $expectedCash;
            $cashAfterDeposit = $cash - $bank;

            $rows[] = [
                'date' => $date,
                'fuel_sales' => round($fuel, 4),
                'cash_sales' => round($cash, 4),
                'card_sales' => round($card, 4),
                'credit_sales' => round($credit, 4),
                'bank_deposited' => round($bank, 4),
                'expected_cash' => round($expectedCash, 4),
                'variation' => round($variation, 4),
                'sales_variation' => round($variation, 4),
                'cash_after_deposit' => round($cashAfterDeposit, 4),
            ];
        }

        $sum = static function (array $rows, string $key): float {
            return round(array_sum(array_map(static fn ($row) => (float) ($row[$key] ?? 0), $rows)), 4);
        };

        $fuel = $sum($rows, 'fuel_sales');
        $cash = $sum($rows, 'cash_sales');
        $card = $sum($rows, 'card_sales');
        $credit = $sum($rows, 'credit_sales');
        $bank = $sum($rows, 'bank_deposited');
        $expectedCash = $fuel - $card - $credit;
        $variation = $cash - $expectedCash;

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'summary' => [
                'fuel_sales' => round($fuel, 4),
                'cash_sales' => round($cash, 4),
                'card_sales' => round($card, 4),
                'credit_sales' => round($credit, 4),
                'bank_deposited' => round($bank, 4),
                'expected_cash' => round($expectedCash, 4),
                'variation' => round($variation, 4),
                'cash_after_deposit' => round($cash - $bank, 4),
                'collection_total' => round($cash + $card + $credit, 4),
            ],
            'daily' => $rows,
        ];
    }

    /**
     * Fuel profitability by product sub-category.
     *
     * Commission income is the realised fuel sales value less the linked/fallback
     * fuel cost. Returns and line discounts are already reflected by the net
     * sold quantity and actual sell price. The requested Net Profit therefore
     * equals this realised commission income until a separate fuel overhead
     * allocation is configured in the source system.
     */
    public function profitability(
        int $businessId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $locationId = null
    ): array {
        [$start, $end] = $this->normaliseRange($startDate, $endDate);
        $fuelIds = $this->fuelProductIds($businessId, $locationId);

        if (! $fuelIds
            || ! Schema::hasTable('transactions')
            || ! Schema::hasTable('transaction_sell_lines')
            || ! Schema::hasTable('products')) {
            return ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'rows' => [], 'totals' => $this->emptyProfitTotals()];
        }

        $subcategoryExpr = Schema::hasTable('categories')
            ? "COALESCE(NULLIF(sc.name,''), NULLIF(p.name,''), 'Uncategorised Fuel')"
            : "COALESCE(NULLIF(p.name,''), 'Uncategorised Fuel')";

        $sales = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id');

        if (Schema::hasTable('categories')) {
            $sales->leftJoin('categories as sc', 'sc.id', '=', 'p.sub_category_id');
        }

        $this->applyFuelSaleScope($sales, $businessId, $start, $end, $locationId, $fuelIds);
        $netQtyExpr = 'GREATEST(COALESCE(tsl.quantity,0)-COALESCE(tsl.quantity_returned,0),0)';
        $salesAmountExpr = $netQtyExpr . ' * COALESCE(tsl.unit_price_inc_tax, tsl.unit_price, 0)';

        $salesRows = $sales
            ->selectRaw("{$subcategoryExpr} as subcategory, SUM({$netQtyExpr}) as qty, SUM({$salesAmountExpr}) as sales_amount")
            ->groupBy('subcategory')
            ->orderBy('subcategory')
            ->get();

        $costs = $this->fuelCostsBySubcategory($businessId, $start, $end, $locationId, $fuelIds, $subcategoryExpr);

        $rows = [];
        foreach ($salesRows as $row) {
            $name = (string) $row->subcategory;
            $qty = (float) $row->qty;
            $salesAmount = (float) $row->sales_amount;
            $cogs = (float) ($costs[$name] ?? 0);
            $commissionIncome = $salesAmount - $cogs;
            $netProfit = $commissionIncome;
            $margin = $salesAmount != 0.0 ? ($netProfit / $salesAmount) * 100 : 0;

            $rows[] = [
                'subcategory' => $name,
                'qty' => round($qty, 3),
                'sales_amount' => round($salesAmount, 4),
                'cost_of_sales' => round($cogs, 4),
                'commission_income' => round($commissionIncome, 4),
                'net_profit' => round($netProfit, 4),
                'commission_per_unit' => $qty > 0 ? round($commissionIncome / $qty, 4) : 0,
                'margin_pct' => round($margin, 2),
            ];
        }

        $totals = [
            'qty' => round(array_sum(array_column($rows, 'qty')), 3),
            'sales_amount' => round(array_sum(array_column($rows, 'sales_amount')), 4),
            'cost_of_sales' => round(array_sum(array_column($rows, 'cost_of_sales')), 4),
            'commission_income' => round(array_sum(array_column($rows, 'commission_income')), 4),
            'net_profit' => round(array_sum(array_column($rows, 'net_profit')), 4),
        ];
        $totals['margin_pct'] = $totals['sales_amount'] != 0.0
            ? round(($totals['net_profit'] / $totals['sales_amount']) * 100, 2)
            : 0;

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    public function debtorsAgeing(
        int $businessId,
        Carbon $asOfDate,
        ?int $locationId = null
    ): array {
        $asOf = $asOfDate->copy()->endOfDay();
        $summary = [
            '0_30' => ['key' => '0_30', 'label' => '0-30 Days', 'amount' => 0.0, 'customers' => 0, 'invoices' => 0],
            '31_60' => ['key' => '31_60', 'label' => '31-60 Days', 'amount' => 0.0, 'customers' => 0, 'invoices' => 0],
            '61_90' => ['key' => '61_90', 'label' => '61-90 Days', 'amount' => 0.0, 'customers' => 0, 'invoices' => 0],
            'over_90' => ['key' => 'over_90', 'label' => 'Over 90 Days', 'amount' => 0.0, 'customers' => 0, 'invoices' => 0],
        ];

        $base = $this->ageingBaseQuery($businessId, $asOf, $locationId);
        if ($base === null) {
            return ['as_of' => $asOf->toDateString(), 'buckets' => array_values($summary), 'total_outstanding' => 0.0];
        }

        $asOfSql = $asOf->toDateString();
        $ageExpr = "DATEDIFF('{$asOfSql}', DATE(t.transaction_date))";
        $bucketExpr = $this->ageingBucketSql($ageExpr);
        $outstandingExpr = $this->outstandingSql();

        $rows = (clone $base)
            ->selectRaw("{$bucketExpr} as bucket, COUNT(DISTINCT t.contact_id) as customers, COUNT(t.id) as invoices, SUM({$outstandingExpr}) as amount")
            ->groupBy('bucket')
            ->get();

        foreach ($rows as $row) {
            $key = (string) $row->bucket;
            if (! isset($summary[$key])) {
                continue;
            }
            $summary[$key]['amount'] = round((float) $row->amount, 4);
            $summary[$key]['customers'] = (int) $row->customers;
            $summary[$key]['invoices'] = (int) $row->invoices;
        }

        return [
            'as_of' => $asOf->toDateString(),
            'buckets' => array_values($summary),
            'total_outstanding' => round(array_sum(array_column($summary, 'amount')), 4),
        ];
    }

    public function debtorsAgeingCustomers(
        int $businessId,
        Carbon $asOfDate,
        string $bucket,
        ?int $locationId = null
    ): array {
        $bucket = in_array($bucket, ['0_30', '31_60', '61_90', 'over_90'], true) ? $bucket : '0_30';
        $asOf = $asOfDate->copy()->endOfDay();
        $base = $this->ageingBaseQuery($businessId, $asOf, $locationId);

        if ($base === null) {
            return ['as_of' => $asOf->toDateString(), 'bucket' => $bucket, 'rows' => [], 'total' => 0.0];
        }

        $asOfSql = $asOf->toDateString();
        $ageExpr = "DATEDIFF('{$asOfSql}', DATE(t.transaction_date))";
        $bucketExpr = $this->ageingBucketSql($ageExpr);
        $outstandingExpr = $this->outstandingSql();

        $rows = (clone $base)
            ->whereRaw("{$bucketExpr} = ?", [$bucket])
            ->selectRaw("c.id as customer_id, c.contact_id as customer_code, c.name as customer_name, c.mobile, COUNT(t.id) as invoices, MIN({$ageExpr}) as min_age_days, MAX({$ageExpr}) as max_age_days, SUM({$outstandingExpr}) as outstanding")
            ->groupBy('c.id', 'c.contact_id', 'c.name', 'c.mobile')
            ->orderByDesc('outstanding')
            ->orderBy('c.name')
            ->get()
            ->map(static function ($row) {
                return [
                    'customer_id' => (int) $row->customer_id,
                    'customer_code' => (string) ($row->customer_code ?? ''),
                    'customer_name' => (string) ($row->customer_name ?? ''),
                    'mobile' => (string) ($row->mobile ?? ''),
                    'invoices' => (int) $row->invoices,
                    'min_age_days' => (int) $row->min_age_days,
                    'max_age_days' => (int) $row->max_age_days,
                    'outstanding' => round((float) $row->outstanding, 4),
                ];
            })
            ->values()
            ->all();

        return [
            'as_of' => $asOf->toDateString(),
            'bucket' => $bucket,
            'rows' => $rows,
            'total' => round(array_sum(array_column($rows, 'outstanding')), 4),
        ];
    }

    private function fuelSalesByDate(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        $fuelIds = $this->fuelProductIds($businessId, $locationId);
        if (! $fuelIds || ! Schema::hasTable('transactions') || ! Schema::hasTable('transaction_sell_lines')) {
            return [];
        }

        $q = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id');
        $this->applyFuelSaleScope($q, $businessId, $start, $end, $locationId, $fuelIds);
        $amountExpr = 'GREATEST(COALESCE(tsl.quantity,0)-COALESCE(tsl.quantity_returned,0),0) * COALESCE(tsl.unit_price_inc_tax, tsl.unit_price, 0)';

        return $q->selectRaw("DATE(t.transaction_date) as report_date, SUM({$amountExpr}) as amount")
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->mapWithKeys(static fn ($row) => [(string) $row->report_date => round((float) $row->amount, 4)])
            ->all();
    }

    private function fuelCreditSalesByDate(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        $fuelIds = $this->fuelProductIds($businessId, $locationId);
        if (! $fuelIds || ! Schema::hasTable('transactions') || ! Schema::hasTable('transaction_sell_lines')) {
            return [];
        }

        $q = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id');
        $this->applyFuelSaleScope($q, $businessId, $start, $end, $locationId, $fuelIds);
        $q->where(function ($credit) {
            if (Schema::hasColumn('transactions', 'is_credit_sale')) {
                $credit->where('t.is_credit_sale', 1);
                if (Schema::hasColumn('transactions', 'sub_type')) {
                    $credit->orWhere('t.sub_type', 'credit_sale');
                }
            } elseif (Schema::hasColumn('transactions', 'sub_type')) {
                $credit->where('t.sub_type', 'credit_sale');
            } else {
                $credit->whereIn('t.payment_status', ['due', 'partial']);
            }
        });

        $amountExpr = 'GREATEST(COALESCE(tsl.quantity,0)-COALESCE(tsl.quantity_returned,0),0) * COALESCE(tsl.unit_price_inc_tax, tsl.unit_price, 0)';
        return $q->selectRaw("DATE(t.transaction_date) as report_date, SUM({$amountExpr}) as amount")
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->mapWithKeys(static fn ($row) => [(string) $row->report_date => round((float) $row->amount, 4)])
            ->all();
    }

    private function legacySettlementCollectionsByDate(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        $out = ['cash' => [], 'card' => [], 'bank_deposit' => []];
        if (! Schema::hasTable('settlements')) {
            return $out;
        }

        $settlements = DB::table('settlements')
            ->where('business_id', $businessId)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when(Schema::hasColumn('settlements', 'status'), static fn ($q) => $q->where('status', 0))
            ->when($locationId, static fn ($q, $locationId) => $q->where('location_id', $locationId))
            ->select('id', 'settlement_no', 'transaction_date')
            ->get();

        if ($settlements->isEmpty()) {
            return $out;
        }

        $keyToDate = [];
        foreach ($settlements as $settlement) {
            $date = Carbon::parse($settlement->transaction_date)->toDateString();
            $keyToDate[(string) $settlement->id] = $date;
            if ((string) $settlement->settlement_no !== '') {
                $keyToDate[(string) $settlement->settlement_no] = $date;
            }
        }
        $keys = array_keys($keyToDate);

        foreach ([
            'cash' => 'settlement_cash_payments',
            'card' => 'settlement_card_payments',
            'bank_deposit' => 'settlement_cash_deposits',
        ] as $type => $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'settlement_no') || ! Schema::hasColumn($table, 'amount')) {
                continue;
            }

            $rows = DB::table($table)
                ->where('business_id', $businessId)
                ->whereIn('settlement_no', $keys)
                ->select('settlement_no', 'amount')
                ->get();

            foreach ($rows as $row) {
                $date = $keyToDate[(string) $row->settlement_no] ?? null;
                if (! $date) {
                    continue;
                }
                $out[$type][$date] = ($out[$type][$date] ?? 0) + (float) $row->amount;
            }
        }

        return $out;
    }

    private function swSettlementCollectionsByDate(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        $out = ['cash' => [], 'card' => [], 'bank_deposit' => []];
        if (! Schema::hasTable('sw_settlements') || ! Schema::hasTable('sw_collections')) {
            return $out;
        }

        $q = DB::table('sw_collections as c')
            ->join('sw_settlements as s', 's.id', '=', 'c.settlement_id')
            ->where('s.business_id', $businessId)
            ->where('s.status', 2)
            ->whereBetween('s.transaction_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('c.payment_method', ['cash', 'card', 'cash_deposit'])
            ->when($locationId, static fn ($query, $locationId) => $query->where('s.location_id', $locationId))
            ->selectRaw('s.transaction_date as report_date, c.payment_method, SUM(c.amount) as amount')
            ->groupBy('s.transaction_date', 'c.payment_method')
            ->get();

        foreach ($q as $row) {
            $type = (string) $row->payment_method;
            if (! isset($out[$type])) {
                continue;
            }
            $date = Carbon::parse($row->report_date)->toDateString();
            $out[$type][$date] = ($out[$type][$date] ?? 0) + (float) $row->amount;
        }

        return $out;
    }

    private function fuelCostsBySubcategory(
        int $businessId,
        Carbon $start,
        Carbon $end,
        ?int $locationId,
        array $fuelIds,
        string $subcategoryExpr
    ): array {
        $costs = [];

        if (Schema::hasTable('transaction_sell_lines_purchase_lines') && Schema::hasTable('purchase_lines')) {
            $mapped = DB::table('transaction_sell_lines_purchase_lines as link')
                ->join('transaction_sell_lines as tsl', 'tsl.id', '=', 'link.sell_line_id')
                ->join('purchase_lines as pl', 'pl.id', '=', 'link.purchase_line_id')
                ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'p.id', '=', 'tsl.product_id');
            if (Schema::hasTable('categories')) {
                $mapped->leftJoin('categories as sc', 'sc.id', '=', 'p.sub_category_id');
            }
            $this->applyFuelSaleScope($mapped, $businessId, $start, $end, $locationId, $fuelIds);
            $mappedCostExpr = 'GREATEST(COALESCE(link.quantity,0)-COALESCE(link.qty_returned,0),0) * COALESCE(pl.purchase_price_inc_tax, pl.purchase_price, 0)';
            foreach ($mapped->selectRaw("{$subcategoryExpr} as subcategory, SUM({$mappedCostExpr}) as cost")->groupBy('subcategory')->get() as $row) {
                $costs[(string) $row->subcategory] = (float) $row->cost;
            }
        }

        // Any quantity that is not linked to a purchase line falls back to the
        // variation's current tax-inclusive purchase cost. This keeps historic
        // or imported fuel sales usable without overstating profit as 100%.
        if (Schema::hasTable('variations')) {
            $mappedQty = null;
            if (Schema::hasTable('transaction_sell_lines_purchase_lines')) {
                $mappedQty = DB::table('transaction_sell_lines_purchase_lines')
                    ->whereNotNull('sell_line_id')
                    ->selectRaw('sell_line_id, SUM(GREATEST(COALESCE(quantity,0)-COALESCE(qty_returned,0),0)) as mapped_qty')
                    ->groupBy('sell_line_id');
            }

            $fallback = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->leftJoin('variations as v', 'v.id', '=', 'tsl.variation_id');
            if ($mappedQty) {
                $fallback->leftJoinSub($mappedQty, 'mq', static function ($join) {
                    $join->on('mq.sell_line_id', '=', 'tsl.id');
                });
            }
            if (Schema::hasTable('categories')) {
                $fallback->leftJoin('categories as sc', 'sc.id', '=', 'p.sub_category_id');
            }
            $this->applyFuelSaleScope($fallback, $businessId, $start, $end, $locationId, $fuelIds);

            $netQty = 'GREATEST(COALESCE(tsl.quantity,0)-COALESCE(tsl.quantity_returned,0),0)';
            $unmappedQty = $mappedQty ? "GREATEST({$netQty}-COALESCE(mq.mapped_qty,0),0)" : $netQty;
            $costColumns = [];
            if (Schema::hasColumn('variations', 'dpp_inc_tax')) {
                $costColumns[] = 'NULLIF(v.dpp_inc_tax, 0)';
            }
            if (Schema::hasColumn('variations', 'default_purchase_price')) {
                $costColumns[] = 'NULLIF(v.default_purchase_price, 0)';
            }
            if (Schema::hasColumn('transaction_sell_lines', 'last_purchased_price')) {
                $costColumns[] = 'NULLIF(tsl.last_purchased_price, 0)';
            }
            $costColumns[] = '0';
            $unitCost = 'COALESCE(' . implode(',', $costColumns) . ')';

            foreach ($fallback->selectRaw("{$subcategoryExpr} as subcategory, SUM({$unmappedQty} * {$unitCost}) as cost")->groupBy('subcategory')->get() as $row) {
                $key = (string) $row->subcategory;
                $costs[$key] = ($costs[$key] ?? 0) + (float) $row->cost;
            }
        }

        return $costs;
    }

    private function ageingBaseQuery(int $businessId, Carbon $asOf, ?int $locationId)
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('contacts')) {
            return null;
        }

        $payments = null;
        if (Schema::hasTable('transaction_payments')) {
            $payments = DB::table('transaction_payments as tp')
                ->where('tp.business_id', $businessId)
                ->when(Schema::hasColumn('transaction_payments', 'deleted_at'), static fn ($q) => $q->whereNull('tp.deleted_at'))
                ->when(Schema::hasColumn('transaction_payments', 'paid_on'), static fn ($q) => $q->where('tp.paid_on', '<=', $asOf))
                ->selectRaw('tp.transaction_id, SUM(CASE WHEN COALESCE(tp.is_return,0)=1 THEN -COALESCE(tp.amount,0) ELSE COALESCE(tp.amount,0) END) as paid_amount')
                ->groupBy('tp.transaction_id');
        }

        $q = DB::table('transactions as t')
            ->join('contacts as c', 'c.id', '=', 't.contact_id');
        if ($payments) {
            $q->leftJoinSub($payments, 'pay', static function ($join) {
                $join->on('pay.transaction_id', '=', 't.id');
            });
        }

        $q->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNotNull('t.contact_id')
            ->where('t.transaction_date', '<=', $asOf)
            ->when(Schema::hasColumn('transactions', 'deleted_at'), static fn ($query) => $query->whereNull('t.deleted_at'))
            ->when($locationId, static fn ($query, $locationId) => $query->where('t.location_id', $locationId))
            ->where(function ($credit) {
                $used = false;
                if (Schema::hasColumn('transactions', 'is_credit_sale')) {
                    $credit->where('t.is_credit_sale', 1);
                    $used = true;
                }
                if (Schema::hasColumn('transactions', 'payment_status')) {
                    if ($used) {
                        $credit->orWhereIn('t.payment_status', ['due', 'partial']);
                    } else {
                        $credit->whereIn('t.payment_status', ['due', 'partial']);
                    }
                }
            })
            ->whereRaw($this->outstandingSql() . ' > 0.00005');

        return $q;
    }

    private function outstandingSql(): string
    {
        $advance = Schema::hasColumn('transactions', 'amount_paid_from_advance')
            ? 'COALESCE(t.amount_paid_from_advance,0)'
            : '0';
        $paid = Schema::hasTable('transaction_payments') ? 'COALESCE(pay.paid_amount,0)' : '0';
        return "GREATEST(COALESCE(t.final_total,0)-{$advance}-{$paid},0)";
    }

    private function ageingBucketSql(string $ageExpr): string
    {
        return "CASE WHEN {$ageExpr} <= 30 THEN '0_30' WHEN {$ageExpr} <= 60 THEN '31_60' WHEN {$ageExpr} <= 90 THEN '61_90' ELSE 'over_90' END";
    }

    private function applyFuelSaleScope($query, int $businessId, Carbon $start, Carbon $end, ?int $locationId, array $fuelIds): void
    {
        $query->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start, $end])
            ->whereIn('tsl.product_id', $fuelIds);
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if ($locationId) {
            $query->where('t.location_id', $locationId);
        }
    }

    private function fuelProductIds(int $businessId, ?int $locationId = null): array
    {
        if (! Schema::hasTable('fuel_tanks')) {
            return [];
        }
        $q = DB::table('fuel_tanks')->where('business_id', $businessId)->whereNotNull('product_id');
        if ($locationId) {
            $q->where('location_id', $locationId);
        }
        return $q->distinct()->pluck('product_id')->map(static fn ($id) => (int) $id)->all();
    }

    private function normaliseRange(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();
        if ($end->lt($start)) {
            $tmp = $start;
            $start = $endDate->copy()->startOfDay();
            $end = $tmp->copy()->endOfDay();
        }
        return [$start, $end];
    }

    private function emptyProfitTotals(): array
    {
        return [
            'qty' => 0.0,
            'sales_amount' => 0.0,
            'cost_of_sales' => 0.0,
            'commission_income' => 0.0,
            'net_profit' => 0.0,
            'margin_pct' => 0.0,
        ];
    }
}
