<?php

namespace Modules\Purchase\Services\Return;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Services\Entry\PurchaseEntryFormService;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;

class PurchaseReturnListService
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

        return [
            'rows' => $this->paginate($request),
            'locations' => $this->form->locations($businessId),
            'suppliers' => $this->suppliers($businessId),
            'filters' => [
                'search' => trim((string) $request->query('search', '')),
                'location_id' => (string) $request->query('location_id', ''),
                'supplier_id' => (string) $request->query('supplier_id', ''),
                'start_date' => (string) $request->query('start_date', ''),
                'end_date' => (string) $request->query('end_date', ''),
            ],
            'summary' => $this->summary($request),
        ];
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->filteredQuery($request)
            ->orderByDesc('t.transaction_date')
            ->orderByDesc('t.id')
            ->paginate(25)
            ->withQueryString();
    }

    /** @return array<string, float|int> */
    protected function summary(Request $request): array
    {
        $row = DB::query()
            ->fromSub($this->filteredQuery($request, false), 'purchase_return_summary')
            ->selectRaw('COUNT(*) as record_count')
            ->selectRaw('COALESCE(SUM(final_total), 0) as total_amount')
            ->first();

        return [
            'record_count' => (int) ($row->record_count ?? 0),
            'total_amount' => (float) ($row->total_amount ?? 0),
        ];
    }

    protected function filteredQuery(Request $request, bool $includeLabels = true): Builder
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $this->numbers->businessId())
            ->where('t.type', 'purchase_return');
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
        $query->leftJoin('transactions as parent', 'parent.id', '=', 't.return_parent_id');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('t.ref_no', 'like', $like)
                    ->orWhere('t.invoice_no', 'like', $like)
                    ->orWhere('parent.invoice_no', 'like', $like)
                    ->orWhere('parent.ref_no', 'like', $like);
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
        if ($request->filled('start_date')) {
            $query->whereDate('t.transaction_date', '>=', (string) $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('t.transaction_date', '<=', (string) $request->query('end_date'));
        }

        $select = [
            't.id', 't.transaction_date', 't.ref_no', 't.invoice_no', 't.final_total',
            't.status', 't.payment_status', 't.contact_id', 't.location_id', 't.return_parent_id',
            DB::raw("COALESCE(parent.invoice_no, parent.ref_no, CONCAT('PUR-', parent.id), '') as parent_purchase_no"),
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

        return $query->select($select);
    }

    protected function suppliers(int $businessId): Collection
    {
        if (! Schema::hasTable('contacts')) {
            return collect();
        }
        $query = DB::table('contacts')->where('business_id', $businessId)->whereIn('type', ['supplier', 'both']);
        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderByRaw("COALESCE(NULLIF(supplier_business_name, ''), name)")
            ->get(['id', 'name', 'supplier_business_name', 'contact_id']);
    }
}
