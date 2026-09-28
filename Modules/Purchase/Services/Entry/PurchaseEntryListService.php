<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;

class PurchaseEntryListService
{
    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseEntryFormService $form
    ) {
    }

    /** @return array<string, mixed> */
    public function pageData(Request $request): array
    {
        $businessId = $this->numbers->businessId();
        $locations = $this->form->locations($businessId);

        return [
            'rows' => $this->paginate($request),
            'locations' => $locations,
            'suppliers' => $this->suppliers($businessId),
            'filters' => [
                'search' => trim((string) $request->query('search', '')),
                'location_id' => (string) $request->query('location_id', ''),
                'supplier_id' => (string) $request->query('supplier_id', ''),
                'status' => (string) $request->query('status', ''),
                'payment_status' => (string) $request->query('payment_status', ''),
                /*
                 * IS2145: default to the CURRENT MONTH.
                 *
                 * Both dates defaulted to an empty string, so the list opened
                 * unfiltered - 131 records here, and growing with every purchase.
                 * The current month is what a user opening this page almost
                 * always wants, and it keeps the query bounded as the table
                 * grows.
                 *
                 * Only applied when the parameter is ABSENT. An explicitly empty
                 * value still means "no filter", so a user who clears the dates
                 * to see everything is not overridden on the next page load.
                 */
                'start_date' => (string) $request->query(
                    'start_date',
                    $request->has('start_date') ? '' : now()->startOfMonth()->format('Y-m-d')
                ),
                'end_date' => (string) $request->query(
                    'end_date',
                    $request->has('end_date') ? '' : now()->endOfMonth()->format('Y-m-d')
                ),
            ],
            'summary' => $this->summary($request),
        ];
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        /*
         * IS2152: "Show N entries" is now honoured.
         *
         * The page size was hardcoded to 25, so the selector had nothing to act
         * on. The value is clamped to a known set rather than taken as given -
         * an arbitrary per_page in the URL would otherwise let anyone ask for
         * every row at once and stall the page.
         */
        $perPage = (int) $request->query('per_page', 25);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        return $this->filteredQuery($request)
            ->orderByDesc('t.transaction_date')
            ->orderByDesc('t.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Every row matching the CURRENT filters, for export.
     *
     * IS2152: exports must cover the whole filtered result, not the page on
     * screen. A CSV of 25 rows when the filter matches 131 is worse than no
     * export at all - it looks complete and is not.
     *
     * filteredQuery() is reused, so an export always matches exactly what the
     * list and the summary cards show. cursor() streams rather than loading
     * every row into memory, so a large date range cannot exhaust it.
     *
     * @return \Generator
     */
    public function exportRows(Request $request)
    {
        return $this->filteredQuery($request)
            ->orderByDesc('t.transaction_date')
            ->orderByDesc('t.id')
            ->cursor();
    }

    /** @return array<string, float|int> */
    protected function summary(Request $request): array
    {
        $query = $this->filteredQuery($request, false);
        $row = DB::query()
            ->fromSub($query, 'purchase_entries_summary')
            ->selectRaw('COUNT(*) as record_count')
            ->selectRaw('COALESCE(SUM(final_total), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->selectRaw('COALESCE(SUM(GREATEST(final_total - paid_amount, 0)), 0) as due_amount')
            ->first();

        return [
            'record_count' => (int) ($row->record_count ?? 0),
            'total_amount' => (float) ($row->total_amount ?? 0),
            'paid_amount' => (float) ($row->paid_amount ?? 0),
            'due_amount' => (float) ($row->due_amount ?? 0),
        ];
    }

    protected function filteredQuery(Request $request, bool $includeLabels = true): Builder
    {
        $businessId = $this->numbers->businessId();
        $query = DB::table('transactions as t')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'purchase');

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }

        if (Schema::hasTable('contacts')) {
            $query->leftJoin('contacts as c', 'c.id', '=', 't.contact_id');
        }
        if (Schema::hasTable('business_locations')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');
        }
        if (Schema::hasTable('stores') && Schema::hasColumn('transactions', 'store_id')) {
            $query->leftJoin('stores as st', 'st.id', '=', 't.store_id');
        }

        $paymentSub = null;
        if (Schema::hasTable('transaction_payments')) {
            $paymentSub = DB::table('transaction_payments')
                ->select('transaction_id')
                ->selectRaw('SUM(COALESCE(amount, 0)) as paid_amount')
                ->groupBy('transaction_id');
            $query->leftJoinSub($paymentSub, 'pay', 'pay.transaction_id', '=', 't.id');
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('t.invoice_no', 'like', $like)
                    ->orWhere('t.ref_no', 'like', $like);
                if (Schema::hasColumn('transactions', 'purchase_entry_no')) {
                    $inner->orWhere('t.purchase_entry_no', 'like', $like);
                }
                if (Schema::hasTable('contacts')) {
                    $inner->orWhere('c.name', 'like', $like)
                        ->orWhere('c.supplier_business_name', 'like', $like)
                        ->orWhere('c.contact_id', 'like', $like);
                }
            });
        }

        if ($request->filled('location_id')) {
            $query->where('t.location_id', (int) $request->query('location_id'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('t.contact_id', (int) $request->query('supplier_id'));
        }
        if ($request->filled('status')) {
            $query->where('t.status', (string) $request->query('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('t.payment_status', (string) $request->query('payment_status'));
        }
        /*
         * IS2145: the same current-month default the filter fields use.
         *
         * The fields above are only what the form DISPLAYS. This is what the
         * query actually applies, and it read the request directly - so
         * defaulting the display alone would have shown the month in the boxes
         * while still listing every record. The two must agree.
         *
         * has() distinguishes "not supplied" from "supplied but empty": a user
         * who clears the dates to see everything gets everything.
         */
        $startDate = $request->has('start_date')
            ? (string) $request->query('start_date')
            : now()->startOfMonth()->format('Y-m-d');

        $endDate = $request->has('end_date')
            ? (string) $request->query('end_date')
            : now()->endOfMonth()->format('Y-m-d');

        if ($startDate !== '') {
            $query->whereDate('t.transaction_date', '>=', $startDate);
        }
        if ($endDate !== '') {
            $query->whereDate('t.transaction_date', '<=', $endDate);
        }

        $select = [
            't.id', 't.transaction_date', 't.invoice_no', 't.ref_no', 't.status',
            't.payment_status', 't.final_total', 't.contact_id', 't.location_id',
        ];
        if (Schema::hasColumn('transactions', 'store_id')) {
            $select[] = 't.store_id';
        }
        if ($includeLabels) {
            $select[] = Schema::hasTable('contacts')
                ? DB::raw("COALESCE(NULLIF(c.supplier_business_name, ''), c.name, '') as supplier_name")
                : DB::raw("'' as supplier_name");
            $select[] = Schema::hasTable('business_locations') ? 'bl.name as location_name' : DB::raw("'' as location_name");
            $select[] = Schema::hasTable('stores') && Schema::hasColumn('transactions', 'store_id')
                ? 'st.name as store_name'
                : DB::raw("'' as store_name");
        }
        $select[] = $paymentSub ? DB::raw('COALESCE(pay.paid_amount, 0) as paid_amount') : DB::raw('0 as paid_amount');
        $select[] = DB::raw('GREATEST(COALESCE(t.final_total, 0) - ' . ($paymentSub ? 'COALESCE(pay.paid_amount, 0)' : '0') . ', 0) as due_amount');

        return $query->select($select);
    }

    protected function suppliers(int $businessId): Collection
    {
        if (! Schema::hasTable('contacts')) {
            return collect();
        }

        $query = DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both']);
        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query
            ->orderByRaw("COALESCE(NULLIF(supplier_business_name, ''), name)")
            ->get([
                'id', 'name', 'supplier_business_name', 'contact_id',
            ]);
    }
}
