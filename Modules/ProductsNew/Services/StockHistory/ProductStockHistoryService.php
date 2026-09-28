<?php

namespace Modules\ProductsNew\Services\StockHistory;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Services\ProductStatusService;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductStockHistoryService
{
    /** @var array<int,int> */
    private array $mainStoreIdCache = [];

    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected ProductStatusService $status
    ) {
    }

    public function report(array $filters): array
    {
        $filters = $this->normaliseFilters($filters);
        $scopeFilters = $filters;
        $scopeFilters['movement_type'] = '';
        $scopeFilters['search'] = '';
        $allMovements = $this->allMovements($scopeFilters);

        $periodContextRows = $allMovements
            ->filter(fn (array $row) => $row['movement_at'] >= $filters['from_at'] && $row['movement_at'] <= $filters['to_at'])
            // Opening stock is the baseline for a bucket and must be applied
            // before the other movements in that bucket. The remaining rows
            // are processed in true date/time order so Balance is a running
            // balance that reconciles row-by-row with the detailed ledger.
            ->sortBy(fn (array $row) => $this->balanceSortKey($row))->values();

        $openingByBucket = $allMovements
            ->filter(fn (array $row) => $row['movement_at'] < $filters['from_at'])
            ->groupBy(fn (array $row) => $this->bucketKey($row))
            ->map(fn (Collection $rows) => round((float) $rows->sum('signed_qty'), 3));

        $running = $openingByBucket->all();
        $periodContextRows = $periodContextRows->map(function (array $row) use (&$running): array {
            $key = $this->bucketKey($row);
            $before = (float) ($running[$key] ?? 0);
            $after = round($before + (float) $row['signed_qty'], 3);
            $running[$key] = $after;
            $row['balance_before'] = $before;
            $row['balance_after'] = $after;
            return $row;
        });

        $periodRows = $periodContextRows->filter(fn (array $row) => $this->matchesViewFilters($row, $filters))->values();
        // Summary follows the selected Product/Location/Store scope and uses all
        // movement types so totals remain reconcilable even when the ledger is
        // narrowed by Movement/Search.
        $summary = $this->summary($allMovements, $periodContextRows, $filters);
        $locationSummary = $this->groupSummary($allMovements, $periodContextRows, $filters, 'location_name');
        $storeSummary = $this->groupSummary($allMovements, $periodContextRows, $filters, 'store_name');
        // IS2247: keep the opening-stock baseline at the top, then show the
        // remaining movements oldest-to-newest by date and time. This makes the
        // displayed order identical to the order used to calculate Balance.
        $displayRows = $periodRows->sort(function (array $left, array $right): int {
            $leftOpening = $left['movement_type'] === 'opening_stock';
            $rightOpening = $right['movement_type'] === 'opening_stock';

            if ($leftOpening !== $rightOpening) {
                return $leftOpening ? -1 : 1;
            }

            return strcmp($this->sortKey($left), $this->sortKey($right));
        })->values();

        $page = max(1, (int) request('page', 1));
        $perPage = min(200, max(25, (int) ($filters['per_page'] ?? 50)));
        $paginator = new LengthAwarePaginator($displayRows->forPage($page, $perPage)->values(), $displayRows->count(), $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
        return compact('filters', 'summary', 'locationSummary', 'storeSummary', 'paginator');
    }

    public function filterOptions(array $filters = []): array
    {
        $businessId = $this->guard->businessId();
        $normalised = $this->normaliseFilters($filters);

        $products = collect();
        if (Schema::hasTable('products')) {
            $productColumns = Schema::getColumnListing('products');
            $productQuery = DB::table('products')->orderBy('name');
            if (in_array('business_id', $productColumns, true)) {
                $productQuery->where('business_id', $businessId);
            }
            $this->status->applyVisibleForDateRange(
                $productQuery,
                $normalised['from_at'],
                $normalised['to_at'],
                'products'
            );

            $products = $productQuery->get([
                'id',
                'name',
                DB::raw(in_array('sku', $productColumns, true) ? 'sku' : "NULL as sku"),
            ]);
        }

        $locations = collect();
        if (Schema::hasTable('business_locations')) {
            $locationColumns = Schema::getColumnListing('business_locations');
            $locationQuery = DB::table('business_locations')->orderBy('name');
            if (in_array('business_id', $locationColumns, true)) {
                $locationQuery->where('business_id', $businessId);
            }
            $locations = $locationQuery->get(['id', 'name']);
        }

        return [
            'products' => $products,
            'locations' => $locations,
            'stores' => $this->stores(),
            'movementTypes' => $this->movementLabels(),
        ];
    }

    private function allMovements(array $filters): Collection
    {
        $rows = collect();
        $rows = $rows->concat($this->standaloneMovements($filters));
        $rows = $rows->concat($this->legacyPurchaseMovements($filters));
        $rows = $rows->concat($this->legacySaleMovements($filters));
        $rows = $rows->concat($this->legacyAdjustmentMovements($filters));

        return $rows
            ->filter(fn (array $row) => $this->matchesScopeFilters($row, $filters))
            ->unique(fn (array $row) => implode('|', [
                $row['source'], $row['source_id'], $row['sequence'], $row['product_id'], $row['variation_id'],
                $row['location_id'], $row['store_id'], $row['movement_type'],
                $row['movement_at']->format('Y-m-d H:i:s'), number_format(abs((float) $row['signed_qty']), 3, '.', ''),
            ]))
            ->values();
    }

    private function standaloneMovements(array $filters): Collection
    {
        if (!Schema::hasTable('products_new_inventory_movements')) {
            return collect();
        }

        $columns = Schema::getColumnListing('products_new_inventory_movements');
        $hasStore = in_array('store_id', $columns, true);
        $dateColumn = in_array('movement_date', $columns, true) ? 'movement_date' : (in_array('movement_at', $columns, true) ? 'movement_at' : 'created_at');
        $qtyColumn = in_array('qty', $columns, true) ? 'qty' : (in_array('quantity', $columns, true) ? 'quantity' : null);
        if (!$qtyColumn || !in_array($dateColumn, $columns, true)) {
            return collect();
        }
        $q = DB::table('products_new_inventory_movements as m')
            ->leftJoin('products as p', 'p.id', '=', 'm.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'm.variation_id')
            ->leftJoin('business_locations as l', 'l.id', '=', 'm.location_id')
            ->where('m.business_id', $this->guard->businessId())
            ->select([
                'm.id', 'm.product_id', 'm.variation_id', 'm.location_id',
                DB::raw($hasStore ? 'm.store_id' : 'NULL as store_id'),
                'm.movement_type', DB::raw("m.`{$dateColumn}` as movement_date"), DB::raw("m.`{$qtyColumn}` as qty"),
                DB::raw(in_array('reference_no', $columns, true) ? 'm.reference_no' : 'NULL as reference_no'),
                DB::raw(in_array('notes', $columns, true) ? 'm.notes' : 'NULL as notes'),
                DB::raw(in_array('created_at', $columns, true) ? 'm.created_at as source_created_at' : 'NULL as source_created_at'),
                'p.name as product_name', 'p.sku', 'v.name as variation_name', 'l.name as location_name',
            ]);

        return $q->get()->map(function ($row): array {
            $type = $this->normaliseMovementType((string) $row->movement_type);
            $signed = $this->signedQty($type, (float) $row->qty);
            return $this->row([
                'sequence' => (int) $row->id,
                'source' => 'Products New',
                'source_id' => (int) $row->id,
                'product_id' => $row->product_id,
                'variation_id' => $row->variation_id,
                'location_id' => $row->location_id,
                'store_id' => $row->store_id,
                'movement_type' => $type,
                'movement_at' => $this->movementDateTime($row->movement_date, $row->source_created_at ?? null),
                'signed_qty' => $signed,
                'reference_no' => $row->reference_no,
                'notes' => $row->notes,
                'product_name' => $row->product_name,
                'sku' => $row->sku,
                'variation_name' => $row->variation_name,
                'location_name' => $row->location_name,
            ]);
        });
    }

    private function legacyPurchaseMovements(array $filters): Collection
    {
        if (!Schema::hasTable('transactions') || !Schema::hasTable('purchase_lines')) {
            return collect();
        }

        $transactionColumns = Schema::getColumnListing('transactions');
        $lineColumns = Schema::getColumnListing('purchase_lines');
        foreach (['id', 'type', 'business_id'] as $required) {
            if (!in_array($required, $transactionColumns, true)) return collect();
        }
        foreach (['id', 'transaction_id', 'product_id', 'variation_id', 'quantity'] as $required) {
            if (!in_array($required, $lineColumns, true)) return collect();
        }

        $dateColumn = in_array('transaction_date', $transactionColumns, true) ? 'transaction_date' : 'created_at';
        $storeExpression = in_array('store_id', $transactionColumns, true) ? 't.store_id' : 'NULL';
        $referenceExpression = in_array('ref_no', $transactionColumns, true) ? 't.ref_no' : (in_array('invoice_no', $transactionColumns, true) ? 't.invoice_no' : 'NULL');
        $types = ['purchase', 'purchase_return', 'opening_stock', 'purchase_transfer'];

        $query = DB::table('transactions as t')
            ->join('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
            ->leftJoin('products as p', 'p.id', '=', 'pl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
            ->leftJoin('business_locations as l', 'l.id', '=', 't.location_id')
            ->where('t.business_id', $this->guard->businessId())
            ->whereIn('t.type', $types);

        if (in_array('ref_no', $transactionColumns, true)) {
            $query->where(function ($where): void {
                $where->whereNull('t.ref_no')
                    ->orWhere('t.ref_no', 'not like', \Modules\ProductsNew\Services\ProductsNewFinanceStockService::MIRROR_REFERENCE_PREFIX . '%');
            });
        }

        return $query->select([
                't.id as transaction_id', 't.type', 't.location_id', 'pl.id as line_id', 'pl.product_id', 'pl.variation_id', 'pl.quantity',
                DB::raw("t.`{$dateColumn}` as movement_date"), DB::raw("{$storeExpression} as store_id"), DB::raw("{$referenceExpression} as reference_no"),
                DB::raw(in_array('created_at', $transactionColumns, true) ? 't.created_at as source_created_at' : 'NULL as source_created_at'),
                DB::raw(in_array('created_at', $lineColumns, true) ? 'pl.created_at as line_created_at' : 'NULL as line_created_at'),
                'p.name as product_name', 'p.sku', 'v.name as variation_name', 'l.name as location_name',
            ])->get()->map(function ($row): array {
                $type = match ($row->type) {
                    'purchase' => 'purchase',
                    'purchase_return' => 'purchase_return',
                    'opening_stock' => 'opening_stock',
                    'purchase_transfer' => 'transfer_in',
                    default => 'purchase',
                };
                return $this->row([
                    'sequence' => (int) $row->line_id,
                    'source' => 'ERP Transaction', 'source_id' => (int) $row->transaction_id,
                    'product_id' => $row->product_id, 'variation_id' => $row->variation_id,
                    'location_id' => $row->location_id, 'store_id' => $row->store_id,
                    'movement_type' => $type, 'movement_at' => $this->legacyTransactionDateTime(
                        $row->movement_date,
                        $row->source_created_at ?? null,
                        $row->line_created_at ?? null
                    ),
                    'signed_qty' => $this->signedQty($type, (float) $row->quantity),
                    'reference_no' => $row->reference_no, 'notes' => null,
                    'product_name' => $row->product_name, 'sku' => $row->sku,
                    'variation_name' => $row->variation_name, 'location_name' => $row->location_name,
                ]);
            });
    }

    private function legacySaleMovements(array $filters): Collection
    {
        if (!Schema::hasTable('transactions') || !Schema::hasTable('transaction_sell_lines')) {
            return collect();
        }

        $transactionColumns = Schema::getColumnListing('transactions');
        $lineColumns = Schema::getColumnListing('transaction_sell_lines');
        if (!in_array('quantity', $lineColumns, true)) {
            return collect();
        }

        $dateColumn = in_array('transaction_date', $transactionColumns, true) ? 'transaction_date' : 'created_at';
        $storeExpression = in_array('store_id', $transactionColumns, true) ? 't.store_id' : 'NULL';
        $referenceExpression = in_array('invoice_no', $transactionColumns, true) ? 't.invoice_no' : (in_array('ref_no', $transactionColumns, true) ? 't.ref_no' : 'NULL');
        $types = ['sell', 'sell_return', 'sell_transfer'];

        return DB::table('transactions as t')
            ->join('transaction_sell_lines as sl', 'sl.transaction_id', '=', 't.id')
            ->leftJoin('products as p', 'p.id', '=', 'sl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'sl.variation_id')
            ->leftJoin('business_locations as l', 'l.id', '=', 't.location_id')
            ->where('t.business_id', $this->guard->businessId())
            ->whereIn('t.type', $types)
            ->select([
                't.id as transaction_id', 't.type', 't.location_id', 'sl.id as line_id', 'sl.product_id', 'sl.variation_id', 'sl.quantity',
                DB::raw("t.`{$dateColumn}` as movement_date"), DB::raw("{$storeExpression} as store_id"), DB::raw("{$referenceExpression} as reference_no"),
                DB::raw(in_array('created_at', $transactionColumns, true) ? 't.created_at as source_created_at' : 'NULL as source_created_at'),
                DB::raw(in_array('created_at', $lineColumns, true) ? 'sl.created_at as line_created_at' : 'NULL as line_created_at'),
                'p.name as product_name', 'p.sku', 'v.name as variation_name', 'l.name as location_name',
            ])->get()->map(function ($row): array {
                $type = match ($row->type) {
                    'sell' => 'sale',
                    'sell_return' => 'sale_return',
                    'sell_transfer' => 'transfer_out',
                    default => 'sale',
                };
                return $this->row([
                    'sequence' => (int) $row->line_id,
                    'source' => 'ERP Transaction', 'source_id' => (int) $row->transaction_id,
                    'product_id' => $row->product_id, 'variation_id' => $row->variation_id,
                    'location_id' => $row->location_id, 'store_id' => $row->store_id,
                    'movement_type' => $type, 'movement_at' => $this->legacyTransactionDateTime(
                        $row->movement_date,
                        $row->source_created_at ?? null,
                        $row->line_created_at ?? null
                    ),
                    'signed_qty' => $this->signedQty($type, (float) $row->quantity),
                    'reference_no' => $row->reference_no, 'notes' => null,
                    'product_name' => $row->product_name, 'sku' => $row->sku,
                    'variation_name' => $row->variation_name, 'location_name' => $row->location_name,
                ]);
            });
    }

    private function legacyAdjustmentMovements(array $filters): Collection
    {
        if (!Schema::hasTable('transactions') || !Schema::hasTable('stock_adjustment_lines')) {
            return collect();
        }
        $transactionColumns = Schema::getColumnListing('transactions');
        $lineColumns = Schema::getColumnListing('stock_adjustment_lines');
        if (!in_array('quantity', $lineColumns, true)) {
            return collect();
        }
        $dateColumn = in_array('transaction_date', $transactionColumns, true) ? 'transaction_date' : 'created_at';
        $storeExpression = in_array('store_id', $transactionColumns, true) ? 't.store_id' : 'NULL';
        $referenceExpression = in_array('ref_no', $transactionColumns, true) ? 't.ref_no' : 'NULL';

        // Stock Adjustment New posts the shared stock_adjustment_lines quantity
        // as an absolute value (the host ERP's legacy convention) and records
        // the real Increase/Decrease direction separately.  Do not infer the
        // direction from the quantity sign when that explicit direction exists.
        // This also fixes historical SAN rows at read time without rewriting
        // posted stock or accounting data.
        $lineDirectionExpression = in_array('stock_adjustment_type', $lineColumns, true)
            ? "CASE WHEN LOWER(TRIM(al.stock_adjustment_type)) IN ('increase','decrease') THEN LOWER(TRIM(al.stock_adjustment_type)) ELSE NULL END"
            : 'NULL';
        $transactionDirectionExpression = in_array('stock_adjustment_type', $transactionColumns, true)
            ? "CASE WHEN LOWER(TRIM(t.stock_adjustment_type)) IN ('increase','decrease') THEN LOWER(TRIM(t.stock_adjustment_type)) ELSE NULL END"
            : 'NULL';
        $explicitDirectionExpression = "COALESCE({$lineDirectionExpression}, {$transactionDirectionExpression})";

        return DB::table('transactions as t')
            ->join('stock_adjustment_lines as al', 'al.transaction_id', '=', 't.id')
            ->leftJoin('products as p', 'p.id', '=', 'al.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'al.variation_id')
            ->leftJoin('business_locations as l', 'l.id', '=', 't.location_id')
            ->where('t.business_id', $this->guard->businessId())
            ->where('t.type', 'stock_adjustment')
            ->select([
                't.id as transaction_id', 't.location_id', 'al.id as line_id', 'al.product_id', 'al.variation_id', 'al.quantity',
                DB::raw("t.`{$dateColumn}` as movement_date"), DB::raw("{$storeExpression} as store_id"), DB::raw("{$referenceExpression} as reference_no"),
                DB::raw("{$explicitDirectionExpression} as explicit_adjustment_direction"),
                DB::raw(in_array('sub_type', $transactionColumns, true) ? 't.sub_type' : 'NULL as transaction_sub_type'),
                DB::raw(in_array('additional_notes', $transactionColumns, true) ? 't.additional_notes as transaction_notes' : 'NULL as transaction_notes'),
                DB::raw(in_array('created_at', $transactionColumns, true) ? 't.created_at as source_created_at' : 'NULL as source_created_at'),
                DB::raw(in_array('created_at', $lineColumns, true) ? 'al.created_at as line_created_at' : 'NULL as line_created_at'),
                'p.name as product_name', 'p.sku', 'v.name as variation_name', 'l.name as location_name',
            ])->get()->map(function ($row): array {
                $explicitDirection = strtolower(trim((string) ($row->explicit_adjustment_direction ?? '')));

                // Some older tenant schemas do not have stock_adjustment_type
                // on the shared transaction/line tables. Stock Adjustment New
                // still identifies its posted direction in additional_notes, so
                // use that as a compatibility fallback before applying the old
                // ERP quantity-sign rule.
                if (! in_array($explicitDirection, ['increase', 'decrease'], true)) {
                    $notes = strtolower((string) ($row->transaction_notes ?? ''));
                    $isStockAdjustmentNew = strtolower((string) ($row->transaction_sub_type ?? '')) === 'stock_adjustment_new'
                        || str_contains($notes, 'stock adjustment new');

                    if ($isStockAdjustmentNew) {
                        if (str_contains($notes, '(increase)')) {
                            $explicitDirection = 'increase';
                        } elseif (str_contains($notes, '(decrease)')) {
                            $explicitDirection = 'decrease';
                        } else {
                            $reference = strtoupper(trim((string) ($row->reference_no ?? '')));
                            if (str_ends_with($reference, '-INC')) {
                                $explicitDirection = 'increase';
                            } elseif (str_ends_with($reference, '-DEC')) {
                                $explicitDirection = 'decrease';
                            }
                        }
                    }
                }

                $type = $explicitDirection === 'increase'
                    ? 'adjustment_in'
                    : ($explicitDirection === 'decrease'
                        ? 'adjustment_out'
                        : (((float) $row->quantity) < 0 ? 'adjustment_in' : 'adjustment_out'));
                return $this->row([
                    'sequence' => (int) $row->line_id,
                    'source' => 'ERP Transaction', 'source_id' => (int) $row->transaction_id,
                    'product_id' => $row->product_id, 'variation_id' => $row->variation_id,
                    'location_id' => $row->location_id, 'store_id' => $row->store_id,
                    'movement_type' => $type, 'movement_at' => $this->legacyTransactionDateTime(
                        $row->movement_date,
                        $row->source_created_at ?? null,
                        $row->line_created_at ?? null
                    ),
                    'signed_qty' => $this->signedQty($type, abs((float) $row->quantity)),
                    'reference_no' => $row->reference_no, 'notes' => null,
                    'product_name' => $row->product_name, 'sku' => $row->sku,
                    'variation_name' => $row->variation_name, 'location_name' => $row->location_name,
                ]);
            });
    }

    private function row(array $data): array
    {
        $data['movement_at'] = Carbon::parse($data['movement_at'] ?: now());

        /*
         * IS2236 - transactions created without an explicit store belong to the
         * location's Main Store for Stock History reporting. This is especially
         * important for opening stock entered while adding a product: older tenant
         * schemas (and legacy ERP rows) can legitimately have a NULL store_id.
         * Resolving the effective store here keeps filtering, grouping, running
         * balances and the detailed ledger consistent without rewriting posted data.
         */
        if (empty($data['store_id'])) {
            $mainStoreId = $this->defaultMainStoreId((int) ($data['location_id'] ?? 0));
            if ($mainStoreId > 0) {
                $data['store_id'] = $mainStoreId;
            }
        }

        $data['store_name'] = $this->storeName($data['store_id'] ?? null);
        $data['location_name'] = $data['location_name'] ?: __('productsnew::stock_history.unassigned_location');
        $data['variation_name'] = $data['variation_name'] ?: '—';
        $data['reference_no'] = $data['reference_no'] ?: '—';
        $isOpeningStock = $data['movement_type'] === 'opening_stock';
        $data['opening_qty'] = $isOpeningStock ? abs((float) $data['signed_qty']) : 0.0;
        $data['qty_in'] = $isOpeningStock ? 0.0 : max(0, (float) $data['signed_qty']);
        $data['qty_out'] = $isOpeningStock ? 0.0 : abs(min(0, (float) $data['signed_qty']));
        $data['movement_label'] = $this->movementLabels()[$data['movement_type']] ?? ucwords(str_replace('_', ' ', $data['movement_type']));
        return $data;
    }

    private function summary(Collection $allRows, Collection $periodRows, array $filters): array
    {
        $carriedOpening = (float) $allRows->filter(fn (array $row) => $row['movement_at'] < $filters['from_at'])->sum('signed_qty');
        $periodOpening = (float) $periodRows->where('movement_type', 'opening_stock')->sum('opening_qty');
        $opening = round($carriedOpening + $periodOpening, 3);
        $nonOpeningRows = $periodRows->reject(fn (array $row) => $row['movement_type'] === 'opening_stock')->values();
        $adjustment = (float) $nonOpeningRows->whereIn('movement_type', ['adjustment_in','adjustment_out','opening_stock_adjustment_in','opening_stock_adjustment_out'])->sum('signed_qty');
        return [
            'opening_stock' => $opening,
            'purchased' => round((float) $nonOpeningRows->where('movement_type','purchase')->sum('qty_in'),3),
            'sold' => round((float) $nonOpeningRows->where('movement_type','sale')->sum('qty_out'),3),
            'purchase_returned' => round((float) $nonOpeningRows->where('movement_type','purchase_return')->sum('qty_out'),3),
            'sale_returned' => round((float) $nonOpeningRows->where('movement_type','sale_return')->sum('qty_in'),3),
            'adjustment' => round($adjustment,3),
            'transferred_in' => round((float) $nonOpeningRows->where('movement_type','transfer_in')->sum('qty_in'),3),
            'transferred_out' => round((float) $nonOpeningRows->where('movement_type','transfer_out')->sum('qty_out'),3),
            'closing_stock' => round($opening + (float) $nonOpeningRows->sum('signed_qty'),3),
        ];
    }

    private function groupSummary(Collection $allRows, Collection $periodRows, array $filters, string $field): Collection
    {
        // The Opening column contains both the carried balance at the beginning
        // of the selected range and any true opening_stock row dated inside the
        // selected range. A true opening stock must never be counted again in
        // Qty In / Net Change.
        $carriedOpeningGroups = $allRows
            ->filter(fn (array $row) => $row['movement_at'] < $filters['from_at'])
            ->groupBy($field)
            ->map(fn (Collection $rows) => round((float) $rows->sum('signed_qty'), 3));

        $periodGroups = $periodRows->groupBy($field);
        $names = $carriedOpeningGroups->keys()->merge($periodGroups->keys())->unique();

        return $names->map(function ($name) use ($carriedOpeningGroups, $periodGroups): array {
            $group = $periodGroups->get($name, collect());
            $periodOpening = round((float) $group
                ->where('movement_type', 'opening_stock')
                ->sum('opening_qty'), 3);
            $opening = round((float) $carriedOpeningGroups->get($name, 0) + $periodOpening, 3);
            $movements = $group
                ->reject(fn (array $row) => $row['movement_type'] === 'opening_stock')
                ->values();

            $in = round((float) $movements->sum('qty_in'), 3);
            $out = round((float) $movements->sum('qty_out'), 3);
            $net = round((float) $movements->sum('signed_qty'), 3);

            return [
                'name' => (string) $name,
                'opening' => $opening,
                'in' => $in,
                'out' => $out,
                'net' => $net,
                'closing' => round($opening + $net, 3),
            ];
        })->sortBy('name')->values();
    }

    private function matchesScopeFilters(array $row, array $filters): bool
    {
        if ($filters['product_id'] && (int)$row['product_id'] !== $filters['product_id']) return false;
        if ($filters['variation_id'] && (int)$row['variation_id'] !== $filters['variation_id']) return false;
        if ($filters['location_id'] && (int)$row['location_id'] !== $filters['location_id']) return false;
        if ($filters['store_id'] && (int)$row['store_id'] !== $filters['store_id']) return false;
        return true;
    }
    private function matchesViewFilters(array $row, array $filters): bool
    {
        if ($filters['movement_type'] && $row['movement_type'] !== $filters['movement_type']) return false;
        if ($filters['search']) {
            $haystack = strtolower(implode(' ',[$row['product_name'],$row['sku'],$row['reference_no'],$row['location_name'],$row['store_name'],$row['movement_label'],$row['notes'] ?? '']));
            if (!str_contains($haystack,strtolower($filters['search']))) return false;
        }
        return true;
    }
    private function sortKey(array $row): string
    {
        // source_id is the transaction/movement id and gives a stable creation
        // sequence when two records have the same second. sequence remains the
        // final tie-breaker for multiple lines on one transaction.
        return $row['movement_at']->format('Y-m-d H:i:s.u')
            . ':' . str_pad((string) ($row['source_id'] ?? 0), 14, '0', STR_PAD_LEFT)
            . ':' . str_pad((string) ($row['sequence'] ?? 0), 14, '0', STR_PAD_LEFT);
    }

    private function balanceSortKey(array $row): string
    {
        // Opening Stock is a baseline, not a normal inbound transaction. It must
        // be applied before same-period stock movements so the first displayed
        // balance is the opening quantity rather than a value already affected by
        // a sale/purchase carrying an incomplete midnight timestamp.
        $priority = $row['movement_type'] === 'opening_stock' ? '0' : '1';

        return $priority . ':' . $this->sortKey($row);
    }

    /**
     * Build the report timestamp for rows coming from the host ERP transaction tables.
     *
     * Legacy transactions commonly use transaction_date as the selected business DATE
     * while created_at records the actual save TIME.  For Stock History we therefore
     * preserve the transaction_date date and use the first real created_at time available.
     * This is intentionally read-only: historical transaction rows are never rewritten.
     */
    private function legacyTransactionDateTime($operationDate, ...$fallbackDates): Carbon
    {
        $firstFallback = null;
        foreach ($fallbackDates as $candidate) {
            if ($candidate) {
                $firstFallback = $candidate;
                break;
            }
        }

        $operation = Carbon::parse($operationDate ?: $firstFallback ?: now());

        foreach ($fallbackDates as $fallbackDate) {
            if (!$fallbackDate) {
                continue;
            }

            try {
                $fallback = Carbon::parse($fallbackDate);
            } catch (\Throwable $e) {
                continue;
            }

            // A created_at value with a real time is the authoritative save time.
            // Keep the selected transaction DATE so back-dated entries stay on the
            // business date chosen by the user, but display/order them by save TIME.
            if ($fallback->format('H:i:s') !== '00:00:00') {
                $operation->setTime($fallback->hour, $fallback->minute, $fallback->second);
                break;
            }
        }

        return $operation;
    }

    private function movementDateTime($operationDate, ...$fallbackDates): Carbon
    {
        $firstFallback = null;
        foreach ($fallbackDates as $candidate) {
            if ($candidate) {
                $firstFallback = $candidate;
                break;
            }
        }

        $operation = Carbon::parse($operationDate ?: $firstFallback ?: now());

        // Some legacy ERP screens save the selected transaction date at 00:00:00
        // even though the transaction/line has a real creation time. Preserve the
        // selected DATE, but recover the best available TIME. This is read-only
        // report logic and never changes the posted transaction.
        if ($operation->format('H:i:s') === '00:00:00') {
            foreach ($fallbackDates as $fallbackDate) {
                if (!$fallbackDate) {
                    continue;
                }

                try {
                    $fallback = Carbon::parse($fallbackDate);
                } catch (\Throwable $e) {
                    continue;
                }

                if ($fallback->format('H:i:s') !== '00:00:00') {
                    $operation->setTime($fallback->hour, $fallback->minute, $fallback->second);
                    break;
                }
            }
        }

        return $operation;
    }

    private function normaliseFilters(array $filters): array
    {
        $from = !empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->startOfDay() : now()->startOfMonth();
        $to = !empty($filters['to_date']) ? Carbon::parse($filters['to_date'])->endOfDay() : now()->endOfDay();
        if ($from->gt($to)) [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        $locationId = (int) ($filters['location_id'] ?? 0);
        $storeId = array_key_exists('store_id', $filters)
            ? (int) ($filters['store_id'] ?? 0)
            : $this->defaultMainStoreId($locationId);

        return [
            'from_date' => $from->toDateString(), 'to_date' => $to->toDateString(), 'from_at' => $from, 'to_at' => $to,
            'product_id' => (int) ($filters['product_id'] ?? 0), 'variation_id' => (int) ($filters['variation_id'] ?? 0),
            'location_id' => $locationId, 'store_id' => $storeId,
            'movement_type' => trim((string) ($filters['movement_type'] ?? '')), 'search' => trim((string) ($filters['search'] ?? '')),
            'per_page' => (int) ($filters['per_page'] ?? 50),
        ];
    }

    private function bucketKey(array $row): string
    {
        return implode(':', [(int) $row['product_id'], (int) $row['variation_id'], (int) $row['location_id'], (int) $row['store_id']]);
    }

    private function normaliseMovementType(string $type): string
    {
        return match ($type) {
            'stock_in', 'purchase_in', 'purchase_received', 'goods_received' => 'purchase',
            'stock_out', 'sale_out', 'sold' => 'sale',
            'return_out', 'purchase_returned', 'supplier_return' => 'purchase_return',
            'return_in', 'sale_returned', 'customer_return' => 'sale_return',
            'transfer_received', 'transferred_in', 'stock_transfer_in' => 'transfer_in',
            'transfer_sent', 'transferred_out', 'stock_transfer_out' => 'transfer_out',
            'stock_adjustment_in' => 'adjustment_in',
            'stock_adjustment_out' => 'adjustment_out',
            'opening_stock_edit_in' => 'opening_stock_adjustment_in',
            'opening_stock_edit_out' => 'opening_stock_adjustment_out',
            default => $type,
        };
    }

    private function signedQty(string $type, float $qty): float
    {
        return in_array($type, [
            'sale', 'purchase_return', 'adjustment_out', 'transfer_out', 'opening_stock_adjustment_out',
        ], true) ? -abs($qty) : abs($qty);
    }

    private function movementLabels(): array
    {
        return [
            'opening_stock' => $this->translatedLabel('productsnew::stock_history.opening_stock', 'Opening Stock'),
            'opening_stock_adjustment_in' => 'Opening Stock Increase',
            'opening_stock_adjustment_out' => 'Opening Stock Reduction',
            'purchase' => $this->translatedLabel('productsnew::stock_history.purchase', 'Purchase'),
            'sale' => $this->translatedLabel('productsnew::stock_history.sale', 'Sale'),
            'purchase_return' => $this->translatedLabel('productsnew::stock_history.purchase_return', 'Purchase Return'),
            'sale_return' => $this->translatedLabel('productsnew::stock_history.sale_return', 'Sale Return'),
            'adjustment_in' => $this->translatedLabel('productsnew::stock_history.adjustment_in', 'Stock Adjustment In'),
            'adjustment_out' => $this->translatedLabel('productsnew::stock_history.adjustment_out', 'Stock Adjustment Out'),
            'transfer_in' => $this->translatedLabel('productsnew::stock_history.transfer_in', 'Transfer In'),
            'transfer_out' => $this->translatedLabel('productsnew::stock_history.transfer_out', 'Transfer Out'),
        ];
    }

    private function translatedLabel(string $key, string $fallback): string
    {
        $translated = __($key);

        return $translated === $key ? $fallback : (string) $translated;
    }

    private function storeTable(): ?string
    {
        foreach (['stores', 'business_stores', 'store_locations'] as $table) {
            if (Schema::hasTable($table)
                && Schema::hasColumn($table, 'id')
                && Schema::hasColumn($table, 'name')) {
                return $table;
            }
        }

        return null;
    }

    private function defaultMainStoreId(int $locationId = 0): int
    {
        if (array_key_exists($locationId, $this->mainStoreIdCache)) {
            return $this->mainStoreIdCache[$locationId];
        }

        $table = $this->storeTable();
        if (!$table) {
            return $this->mainStoreIdCache[$locationId] = 0;
        }

        $query = DB::table($table)->whereRaw("LOWER(TRIM(name)) = ?", ['main store']);

        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $this->guard->businessId());
        }
        if (Schema::hasColumn($table, 'status')) {
            $query->where('status', 1);
        }
        if ($locationId > 0 && Schema::hasColumn($table, 'location_id')) {
            $query->where('location_id', $locationId);
        }

        return $this->mainStoreIdCache[$locationId] = (int) ($query->orderBy('id')->value('id') ?? 0);
    }

    private function stores(): Collection
    {
        $table = $this->storeTable();
        if (!$table) {
            return collect();
        }

        $columns = Schema::getColumnListing($table);
        $q = DB::table($table)->select('id', 'name');
        if (in_array('business_id', $columns, true)) {
            $q->where('business_id', $this->guard->businessId());
        }

        return $q->orderBy('name')->get();
    }

    private function storeName($storeId): string
    {
        if (!$storeId) return $this->translatedLabel('productsnew::stock_history.unassigned_store', 'Unassigned Store');
        static $map;
        $map ??= $this->stores()->pluck('name', 'id');
        return (string) ($map[$storeId] ?? ('Store #' . $storeId));
    }
}
