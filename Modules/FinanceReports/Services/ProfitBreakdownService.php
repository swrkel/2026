<?php

namespace Modules\FinanceReports\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IS2059: Profit & Loss broken down by dimension.
 *
 * The report showed only a single income-versus-expenses summary. The ticket
 * asks for nine breakdowns, each on its own tab:
 *
 *   products, categories, sub categories, brands, locations,
 *   invoice, date, customer, day
 *
 *
 * HOW PROFIT IS CALCULATED
 *
 * IS2216 accounting rules:
 *
 *     revenue      = sold-line amount BEFORE discount
 *     cost         = posted COGS account_transactions for the sold line
 *     gross profit = revenue - cost - discount
 *     margin       = gross profit / (revenue - discount)
 *
 * Cost is deliberately taken from the Accounting/Finance COGS ledger, not
 * recomputed from purchase prices. This makes the Profit Breakdown COST total
 * reconcile to the COGS Account Book exactly. If a valid COGS posting cannot be
 * attributed to a sell line, a visible "COGS Ledger Adjustment / Unallocated"
 * row is added so the report total still equals the accounting ledger instead
 * of silently inventing a product/category allocation.
 *
 * Returned quantity is deducted from quantity/revenue. COGS reversals are read
 * from the ledger as credits and therefore reduce cost automatically.
 *
 * Only completed sells are counted - type 'sell' with status 'final'. Drafts and
 * quotations are excluded, as they are not income.
 *
 *
 * WHY ONE QUERY SHAPE, NINE GROUPINGS
 *
 * Every tab is the same calculation grouped differently, so there is ONE base
 * query and the grouping column is swapped. Nine separate queries would have
 * drifted apart the first time the profit rule changed - which is exactly the
 * class of bug that has bitten these modules repeatedly.
 *
 * Each tab is computed only when it is asked for, so opening the report runs one
 * grouped query, not nine.
 */
class ProfitBreakdownService
{
    /**
     * The tabs, in the order the ticket lists them.
     *
     * 'label'  - tab caption
     * 'column' - what the first column is headed
     */
    public const TABS = [
        'products'       => ['label' => 'Profit by Products',       'column' => 'Product'],
        'categories'     => ['label' => 'Profit by Categories',     'column' => 'Category'],
        'sub_categories' => ['label' => 'Profit by Sub Categories', 'column' => 'Sub Category'],
        'brands'         => ['label' => 'Profit by Brands',         'column' => 'Brand'],
        'locations'      => ['label' => 'Profit by Locations',      'column' => 'Location'],
        'invoice'        => ['label' => 'Profit by Invoice',        'column' => 'Invoice No'],
        'date'           => ['label' => 'Profit by Date',           'column' => 'Date'],
        'customer'       => ['label' => 'Profit by Customer',       'column' => 'Customer'],
        'day'            => ['label' => 'Profit by Day',            'column' => 'Day'],
    ];

    public function tabs(): array
    {
        return self::TABS;
    }

    public function isValidTab(?string $tab): bool
    {
        return $tab !== null && array_key_exists($tab, self::TABS);
    }

    /**
     * Rows for one tab: label, quantity, revenue, cost, profit, margin.
     */
    public function breakdown(
        int $businessId,
        string $tab,
        string $startDate,
        string $endDate,
        $locationId = null,
        bool $splitByLocation = false
    ): Collection {
        if (! $this->isValidTab($tab) || ! $this->tablesPresent()) {
            return collect();
        }

        $grouping = $this->grouping($tab);

        if ($grouping === null) {
            return collect();
        }

        /*
         * IS2059: optional split by location.
         *
         * With "All" locations selected, a product's figures from every site are
         * merged into one row - so a product that is profitable at one station
         * and sold below cost at another looks merely average, and the problem is
         * invisible.
         *
         * Splitting adds location to the GROUP BY, giving one row per
         * dimension-and-location pair, and a Location column to read it by.
         *
         * Off by default: it multiplies the row count, and the merged view is the
         * right one when comparing products overall. Pointless on the locations
         * tab itself - that already groups by location - so it is ignored there.
         * Pointless too when a single location is filtered, since every row would
         * carry the same value.
         */
        $splitByLocation = $splitByLocation
            && $tab !== 'locations'
            && empty($locationId);

        $query = $this->baseQuery($businessId, $startDate, $endDate, $locationId);

        foreach ($grouping['joins'] as $join) {
            $join($query);
        }

        if ($splitByLocation) {
            // Aliased split_bl so it cannot collide with the bl join the
            // locations tab makes.
            $query->leftJoin('business_locations as split_bl', 'split_bl.id', '=', 't.location_id');
        }

        $query
            ->selectRaw($grouping['select'] . ' AS group_label')
            ->selectRaw('COALESCE(SUM(' . $this->soldQtyExpr() . '), 0) AS quantity')
            ->selectRaw('COALESCE(SUM(' . $this->revenueExpr() . '), 0) AS revenue')
            ->selectRaw('COALESCE(SUM(' . $this->costExpr() . '), 0) AS cost')
            ->selectRaw('COALESCE(SUM(' . $this->discountExpr() . '), 0) AS discount');

        if ($splitByLocation) {
            $query->selectRaw('split_bl.name AS location_label')
                ->groupByRaw($grouping['group'] . ', split_bl.id, split_bl.name');
        } else {
            $query->groupByRaw($grouping['group']);
        }

        $rows = $query
            ->orderByRaw('SUM((' . $this->revenueExpr() . ') - (' . $this->costExpr() . ') - (' . $this->discountExpr() . ')) DESC')
            ->get()
            ->map(function ($row) {
                $revenue = (float) $row->revenue;
                $cost = (float) $row->cost;
                $discount = (float) $row->discount;
                $netRevenue = $revenue - $discount;
                $profit = $revenue - $cost - $discount;

                return (object) [
                    'label' => $row->group_label !== null && $row->group_label !== ''
                        ? $row->group_label
                        : '—',
                    'location_label' => isset($row->location_label) && $row->location_label !== ''
                        ? $row->location_label
                        : null,
                    'quantity' => (float) $row->quantity,
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'discount' => round($discount, 2),
                    'profit' => round($profit, 2),
                    'margin' => abs($netRevenue) > 0.000001
                        ? round(($profit / $netRevenue) * 100, 2)
                        : 0.0,
                ];
            });

        $ledgerCost = $this->cogsLedgerTotal($businessId, $startDate, $endDate, $locationId);
        $allocatedCost = (float) $rows->sum('cost');
        $difference = round($ledgerCost - $allocatedCost, 2);

        if (abs($difference) >= 0.01) {
            $rows->push((object) [
                'label' => 'COGS Ledger Adjustment / Unallocated',
                'location_label' => null,
                'quantity' => 0.0,
                'revenue' => 0.0,
                'cost' => $difference,
                'discount' => 0.0,
                'profit' => round(-$difference, 2),
                'margin' => 0.0,
            ]);
        }

        return $rows;
    }

    /**
     * Column totals, so the footer agrees with the rows above it.
     */
    public function totals(Collection $rows): array
    {
        $revenue = (float) $rows->sum('revenue');
        $cost = (float) $rows->sum('cost');
        $discount = (float) $rows->sum('discount');
        $netRevenue = $revenue - $discount;
        $profit = $revenue - $cost - $discount;

        return [
            'quantity' => round((float) $rows->sum('quantity'), 2),
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'discount' => round($discount, 2),
            'profit' => round($profit, 2),
            'margin' => abs($netRevenue) > 0.000001
                ? round(($profit / $netRevenue) * 100, 2)
                : 0.0,
        ];
    }

    /* ------------------------------------------------------------------ *
     * Query building
     * ------------------------------------------------------------------ */

    private function baseQuery(int $businessId, string $startDate, string $endDate, $locationId = null)
    {
        $cogsByLine = $this->cogsBySellLineQuery($businessId, $startDate, $endDate, $locationId);
        $transactionNet = DB::table('transaction_sell_lines as tx_net_line')
            ->select('tx_net_line.transaction_id')
            ->selectRaw(
                'SUM((COALESCE(tx_net_line.quantity, 0) - COALESCE(tx_net_line.quantity_returned, 0))'
                . ' * COALESCE(tx_net_line.unit_price_inc_tax, 0)) AS net_revenue'
            )
            ->groupBy('tx_net_line.transaction_id');

        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoinSub($cogsByLine, 'cogs_line', function ($join) {
                $join->on('cogs_line.sell_line_id', '=', 'tsl.id');
            })
            ->leftJoinSub($transactionNet, 'tx_net', function ($join) {
                $join->on('tx_net.transaction_id', '=', 't.id');
            })
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $startDate)
            ->whereDate('t.transaction_date', '<=', $endDate);

        if (! empty($locationId)) {
            $query->where('t.location_id', $locationId);
        }

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if (Schema::hasColumn('transactions', 'new_deleted_at')) {
            $query->whereNull('t.new_deleted_at');
        }

        return $query;
    }

    private function soldQtyExpr(): string
    {
        return '(COALESCE(tsl.quantity, 0) - COALESCE(tsl.quantity_returned, 0))';
    }

    private function netRevenueExpr(): string
    {
        return '(' . $this->soldQtyExpr() . ') * COALESCE(tsl.unit_price_inc_tax, 0)';
    }

    private function lineDiscountExpr(): string
    {
        if (! Schema::hasColumn('transaction_sell_lines', 'line_discount_amount')) {
            return '0';
        }

        $amount = 'COALESCE(tsl.line_discount_amount, 0)';

        if (! Schema::hasColumn('transaction_sell_lines', 'line_discount_type')) {
            return '(' . $this->soldQtyExpr() . ') * ' . $amount;
        }

        $percentageBase = Schema::hasColumn('transaction_sell_lines', 'unit_price_before_discount')
            ? 'COALESCE(tsl.unit_price_before_discount, COALESCE(tsl.unit_price_inc_tax, 0))'
            : 'COALESCE(tsl.unit_price_inc_tax, 0)';

        return '(CASE'
            . " WHEN tsl.line_discount_type = 'percentage'"
            . ' THEN (' . $this->soldQtyExpr() . ') * (' . $percentageBase . ') * (' . $amount . ' / 100)'
            . ' ELSE (' . $this->soldQtyExpr() . ') * ' . $amount
            . ' END)';
    }

    private function transactionDiscountExpr(): string
    {
        if (! Schema::hasColumn('transactions', 'discount_amount')) {
            return '0';
        }

        $amount = 'COALESCE(t.discount_amount, 0)';
        $netRevenue = '(' . $this->netRevenueExpr() . ')';

        if (! Schema::hasColumn('transactions', 'discount_type')) {
            return '(' . $amount . ' * (' . $netRevenue . ' / NULLIF(tx_net.net_revenue, 0)))';
        }

        return '(CASE'
            . " WHEN t.discount_type = 'percentage'"
            . ' THEN ' . $netRevenue . ' * (' . $amount . ' / 100)'
            . " WHEN t.discount_type = 'fixed'"
            . ' THEN ' . $amount . ' * (' . $netRevenue . ' / NULLIF(tx_net.net_revenue, 0))'
            . ' ELSE 0 END)';
    }

    private function discountExpr(): string
    {
        return '(COALESCE(' . $this->lineDiscountExpr() . ', 0) + COALESCE(' . $this->transactionDiscountExpr() . ', 0))';
    }

    private function revenueExpr(): string
    {
        /*
         * IS2220: transaction_sell_lines.unit_price_inc_tax already represents
         * the sale amount before the settlement discount is deducted. The
         * previous code added the discount to that amount again, inflating
         * Revenue (for example 36,875.00 + 3,687.50 = 40,562.50).
         *
         * Revenue must therefore be the gross sold-line amount by itself;
         * Discount remains a separate column and is deducted once when Gross
         * Profit / Margin are calculated.
         */
        return '(' . $this->netRevenueExpr() . ')';
    }

    private function costExpr(): string
    {
        return 'COALESCE(cogs_line.ledger_cost, 0)';
    }

    private function cogsBySellLineQuery(
        int $businessId,
        string $startDate,
        string $endDate,
        $locationId = null
    ) {
        $ids = $this->cogsAccountIds($businessId);
        $sellLineExpr = $this->accountTransactionSellLineExpr('cogs_at');
        $query = $this->cogsPostingQuery(
            'cogs_at',
            $businessId,
            $startDate,
            $endDate,
            $locationId,
            $ids
        );

        if ($sellLineExpr === null) {
            return $query
                ->selectRaw('NULL AS sell_line_id')
                ->selectRaw('0 AS ledger_cost')
                ->whereRaw('1 = 0');
        }

        return $query
            ->whereRaw($sellLineExpr . ' IS NOT NULL')
            ->selectRaw($sellLineExpr . ' AS sell_line_id')
            ->selectRaw(
                "SUM(CASE WHEN cogs_at.type = 'debit' THEN cogs_at.amount ELSE -cogs_at.amount END) AS ledger_cost"
            )
            ->groupByRaw($sellLineExpr);
    }

    private function cogsLedgerTotal(
        int $businessId,
        string $startDate,
        string $endDate,
        $locationId = null
    ): float {
        $ids = $this->cogsAccountIds($businessId);

        if (empty($ids)) {
            return 0.0;
        }

        $row = $this->cogsPostingQuery(
            'cogs_total',
            $businessId,
            $startDate,
            $endDate,
            $locationId,
            $ids
        )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN cogs_total.type = 'debit' THEN cogs_total.amount ELSE -cogs_total.amount END), 0) AS ledger_cost"
            )
            ->first();

        return round((float) ($row->ledger_cost ?? 0), 2);
    }

    private function cogsPostingQuery(
        string $alias,
        int $businessId,
        string $startDate,
        string $endDate,
        $locationId,
        array $accountIds
    ) {
        $accountAlias = $alias . '_account';
        $directAlias = $alias . '_tx';
        $paymentAlias = $alias . '_payment';
        $paymentTxAlias = $alias . '_payment_tx';

        $query = DB::table('account_transactions as ' . $alias)
            ->leftJoin('accounts as ' . $accountAlias, $accountAlias . '.id', '=', $alias . '.account_id')
            ->whereIn($alias . '.account_id', $accountIds ?: [-1])
            ->where(function ($business) use ($alias, $accountAlias, $businessId) {
                $business->where($alias . '.business_id', $businessId)
                    ->orWhere(function ($inherited) use ($alias, $accountAlias, $businessId) {
                        $inherited->where(function ($missing) use ($alias) {
                            $missing->whereNull($alias . '.business_id')
                                ->orWhere($alias . '.business_id', 0);
                        })->where($accountAlias . '.business_id', $businessId);
                    });
            })
            ->where($alias . '.operation_date', '>=', Carbon::parse($startDate)->startOfDay())
            ->where($alias . '.operation_date', '<', Carbon::parse($endDate)->addDay()->startOfDay());

        $this->applyActiveAccountingFilters($query, $alias);

        $transactionLocationColumn = null;
        if (Schema::hasTable('transactions')) {
            if (Schema::hasColumn('transactions', 'location_id')) {
                $transactionLocationColumn = 'location_id';
            } elseif (Schema::hasColumn('transactions', 'business_location_id')) {
                $transactionLocationColumn = 'business_location_id';
            }
        }

        $hasDirect = Schema::hasTable('transactions')
            && Schema::hasColumn('account_transactions', 'transaction_id');

        if ($hasDirect) {
            $query->leftJoin(
                'transactions as ' . $directAlias,
                $directAlias . '.id',
                '=',
                $alias . '.transaction_id'
            );
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $query->whereNull($directAlias . '.deleted_at');
            }
            if (Schema::hasColumn('transactions', 'new_deleted_at')) {
                $query->whereNull($directAlias . '.new_deleted_at');
            }
        }

        $hasPayment = Schema::hasTable('transaction_payments')
            && Schema::hasTable('transactions')
            && Schema::hasColumn('account_transactions', 'transaction_payment_id')
            && Schema::hasColumn('transaction_payments', 'transaction_id');

        if ($hasPayment) {
            $query->leftJoin(
                'transaction_payments as ' . $paymentAlias,
                $paymentAlias . '.id',
                '=',
                $alias . '.transaction_payment_id'
            )->leftJoin(
                'transactions as ' . $paymentTxAlias,
                $paymentTxAlias . '.id',
                '=',
                $paymentAlias . '.transaction_id'
            );

            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $query->whereNull($paymentAlias . '.deleted_at');
            }
            if (Schema::hasColumn('transaction_payments', 'is_return')) {
                $query->where(function ($activePayment) use ($paymentAlias) {
                    $activePayment->whereNull($paymentAlias . '.is_return')
                        ->orWhere($paymentAlias . '.is_return', 0);
                });
            }
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $query->whereNull($paymentTxAlias . '.deleted_at');
            }
            if (Schema::hasColumn('transactions', 'new_deleted_at')) {
                $query->whereNull($paymentTxAlias . '.new_deleted_at');
            }
        }

        if (! empty($locationId)) {
            $hasAtLocation = Schema::hasColumn('account_transactions', 'location_id');
            $hasDirectLocation = $hasDirect && $transactionLocationColumn !== null;
            $hasPaymentLocation = $hasPayment && $transactionLocationColumn !== null;
            $hasAccountLocation = Schema::hasColumn('accounts', 'location_id');

            if (! ($hasAtLocation || $hasDirectLocation || $hasPaymentLocation || $hasAccountLocation)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($location) use (
                    $alias,
                    $accountAlias,
                    $directAlias,
                    $paymentTxAlias,
                    $locationId,
                    $hasAtLocation,
                    $hasDirectLocation,
                    $hasPaymentLocation,
                    $hasAccountLocation,
                    $transactionLocationColumn
                ) {
                    $location->whereRaw('1 = 0');

                    if ($hasAtLocation) {
                        $location->orWhere($alias . '.location_id', $locationId);
                    }

                    if ($hasDirectLocation) {
                        $location->orWhere(function ($direct) use (
                            $alias,
                            $directAlias,
                            $locationId,
                            $hasAtLocation,
                            $transactionLocationColumn
                        ) {
                            if ($hasAtLocation) {
                                $direct->where(function ($missingAt) use ($alias) {
                                    $missingAt->whereNull($alias . '.location_id')
                                        ->orWhere($alias . '.location_id', '');
                                });
                            }
                            $direct->where($directAlias . '.' . $transactionLocationColumn, $locationId);
                        });
                    }

                    if ($hasPaymentLocation) {
                        $location->orWhere(function ($payment) use (
                            $alias,
                            $directAlias,
                            $paymentTxAlias,
                            $locationId,
                            $hasAtLocation,
                            $hasDirectLocation,
                            $transactionLocationColumn
                        ) {
                            if ($hasAtLocation) {
                                $payment->where(function ($missingAt) use ($alias) {
                                    $missingAt->whereNull($alias . '.location_id')
                                        ->orWhere($alias . '.location_id', '');
                                });
                            }
                            if ($hasDirectLocation) {
                                $payment->whereNull($directAlias . '.' . $transactionLocationColumn);
                            }
                            $payment->where($paymentTxAlias . '.' . $transactionLocationColumn, $locationId);
                        });
                    }

                    if ($hasAccountLocation) {
                        $location->orWhere(function ($accountLocation) use (
                            $alias,
                            $accountAlias,
                            $directAlias,
                            $paymentTxAlias,
                            $locationId,
                            $hasAtLocation,
                            $hasDirectLocation,
                            $hasPaymentLocation,
                            $transactionLocationColumn
                        ) {
                            if ($hasAtLocation) {
                                $accountLocation->where(function ($missingAt) use ($alias) {
                                    $missingAt->whereNull($alias . '.location_id')
                                        ->orWhere($alias . '.location_id', '');
                                });
                            }
                            if ($hasDirectLocation) {
                                $accountLocation->whereNull($directAlias . '.' . $transactionLocationColumn);
                            }
                            if ($hasPaymentLocation) {
                                $accountLocation->whereNull($paymentTxAlias . '.' . $transactionLocationColumn);
                            }
                            $accountLocation->where($accountAlias . '.location_id', (string) $locationId);
                        });
                    }
                });
            }
        }

        return $query;
    }

    private function accountTransactionSellLineExpr(string $alias): ?string
    {
        $columns = [];

        if (Schema::hasColumn('account_transactions', 'sell_line_id')) {
            $columns[] = $alias . '.sell_line_id';
        }
        if (Schema::hasColumn('account_transactions', 'transaction_sell_line_id')) {
            $columns[] = $alias . '.transaction_sell_line_id';
        }

        if (empty($columns)) {
            return null;
        }

        return count($columns) === 1
            ? $columns[0]
            : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function cogsAccountIds(int $businessId): array
    {
        $query = DB::table('accounts as cogs_account')
            ->where('cogs_account.business_id', $businessId);

        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('cogs_account.deleted_at');
        }
        $hasGroups = Schema::hasTable('account_groups');

        if ($hasGroups) {
            $query->leftJoin('account_groups as cogs_group', 'cogs_group.id', '=', 'cogs_account.asset_type');
        }

        $query->where(function ($cogs) use ($hasGroups) {
            $cogs->whereRaw("LOWER(COALESCE(cogs_account.name, '')) LIKE ?", ['%cost of goods%'])
                ->orWhereRaw("LOWER(COALESCE(cogs_account.name, '')) LIKE ?", ['%cogs%']);

            if ($hasGroups) {
                $cogs->orWhereRaw("LOWER(COALESCE(cogs_group.name, '')) LIKE ?", ['%cost of goods%'])
                    ->orWhereRaw("LOWER(COALESCE(cogs_group.name, '')) LIKE ?", ['%cogs%']);

                if (Schema::hasColumn('account_groups', 'default_account_group_id')) {
                    $cogs->orWhere('cogs_group.default_account_group_id', 8);
                }
            }
        });

        return $query->pluck('cogs_account.id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function applyActiveAccountingFilters($query, string $alias): void
    {
        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }
        if (Schema::hasColumn('account_transactions', 'new_deleted_at')) {
            $query->whereNull($alias . '.new_deleted_at');
        }
        if (Schema::hasColumn('account_transactions', 'reversed')) {
            $query->where(function ($active) use ($alias) {
                $active->whereNull($alias . '.reversed')->orWhere($alias . '.reversed', 0);
            });
        }
        if (Schema::hasColumn('account_transactions', 'journal_deleted')) {
            $query->where(function ($active) use ($alias) {
                $active->whereNull($alias . '.journal_deleted')->orWhere($alias . '.journal_deleted', 0);
            });
        }
    }

    /**
     * The grouping for each tab: what to select, what to group by, and any extra
     * joins the dimension needs.
     */
    private function grouping(string $tab): ?array
    {
        $none = [];

        switch ($tab) {
            case 'products':
                return [
                    'select' => "CONCAT(p.name, IF(p.sku IS NULL OR p.sku = '', '', CONCAT(' (', p.sku, ')')))",
                    'group' => 'p.id, p.name, p.sku',
                    'joins' => $none,
                ];

            case 'categories':
                return [
                    'select' => 'cat.name',
                    'group' => 'cat.id, cat.name',
                    'joins' => [function ($q) {
                        $q->leftJoin('categories as cat', 'cat.id', '=', 'p.category_id');
                    }],
                ];

            case 'sub_categories':
                return [
                    'select' => 'sub.name',
                    'group' => 'sub.id, sub.name',
                    'joins' => [function ($q) {
                        $q->leftJoin('categories as sub', 'sub.id', '=', 'p.sub_category_id');
                    }],
                ];

            case 'brands':
                return [
                    'select' => 'br.name',
                    'group' => 'br.id, br.name',
                    'joins' => [function ($q) {
                        $q->leftJoin('brands as br', 'br.id', '=', 'p.brand_id');
                    }],
                ];

            case 'locations':
                return [
                    'select' => 'bl.name',
                    'group' => 'bl.id, bl.name',
                    'joins' => [function ($q) {
                        $q->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');
                    }],
                ];

            case 'invoice':
                return [
                    'select' => 't.invoice_no',
                    'group' => 't.id, t.invoice_no',
                    'joins' => $none,
                ];

            case 'date':
                return [
                    'select' => 'DATE(t.transaction_date)',
                    'group' => 'DATE(t.transaction_date)',
                    'joins' => $none,
                ];

            case 'customer':
                return [
                    'select' => "COALESCE(NULLIF(TRIM(c.name), ''), c.supplier_business_name)",
                    'group' => 'c.id, c.name, c.supplier_business_name',
                    'joins' => [function ($q) {
                        $q->leftJoin('contacts as c', 'c.id', '=', 't.contact_id');
                    }],
                ];

            case 'day':
                // Day of the week, so trading patterns show up across the period.
                return [
                    'select' => 'DAYNAME(t.transaction_date)',
                    'group' => 'DAYOFWEEK(t.transaction_date), DAYNAME(t.transaction_date)',
                    'joins' => $none,
                ];
        }

        return null;
    }

    /**
     * Guarded so a deployment without these tables shows an empty report rather
     * than erroring.
     */
    private function tablesPresent(): bool
    {
        foreach (['transactions', 'transaction_sell_lines', 'products', 'account_transactions', 'accounts'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
