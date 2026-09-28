<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\SW\Entities\Settlement;
use Modules\SW\Entities\Shift;

/**
 * SW Settlement list and printable settlement detail.
 *
 * A settlement may contain several CLOSED shifts. The relationship therefore
 * lives through sw_settlement_shifts; there is no single sw_shift_id on the
 * settlement header.
 */
class SettlementController extends Controller
{
    protected function businessId(): int
    {
        // S753: use the same tenant/business resolution as New Settlement and
        // Save Settlement. A stale session value from another tenant host must
        // not hide the row that was just committed after the success redirect.
        $userBusinessId = (int) (optional(auth()->user())->business_id ?? 0);

        return $userBusinessId > 0
            ? $userBusinessId
            : (int) (session('business.id') ?: session('user.business_id') ?: session('business_id') ?: 0);
    }

    protected function authorizeSettlementView(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('superadmin') || $user->can('sw.settlement.view')),
            403
        );
    }

    public function index(Request $request)
    {
        $this->authorizeSettlementView();
        $businessId = $this->businessId();

        $settlements = Settlement::where('business_id', $businessId)
            ->with('shifts')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25);

        // Closed shifts with no settlement yet - what can be settled now.
        $awaiting = Shift::where('business_id', $businessId)
            ->where(function ($q) {
                $q->whereRaw("LOWER(TRIM(CAST(status AS CHAR))) IN ('1', 'closed', 'close')")
                  ->orWhere(function ($legacy) {
                      $legacy->whereRaw("LOWER(TRIM(CAST(status AS CHAR))) IN ('0', 'open', 'opened')")
                             ->whereNotNull('closed_at');
                  });
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('sw_settlement_shifts')
                    ->whereColumn('sw_settlement_shifts.sw_shift_id', 'sw_shifts.id');
            })
            ->orderByDesc('shift_date')
            ->get();

        return view('sw::settlements.index', compact('settlements', 'awaiting'));
    }

    public function show(int $id)
    {
        return $this->renderSettlementDetail($id, false);
    }

    public function printView(int $id)
    {
        return $this->renderSettlementDetail($id, true);
    }

    /**
     * S734 - one authoritative detail/print form for a saved SW settlement.
     * The view includes every sw_collections payment, detailed Credit Sales and
     * every operator linked to every SW Shift attached to the settlement.
     */
    protected function renderSettlementDetail(int $id, bool $printMode)
    {
        $this->authorizeSettlementView();
        $businessId = $this->businessId();

        $settlement = Settlement::where('business_id', $businessId)
            ->with(['shifts', 'lines', 'otherSales', 'otherIncome', 'creditSales'])
            ->findOrFail($id);

        $shiftIds = $settlement->shifts
            ->pluck('id')
            ->map(static fn ($value) => (int) $value)
            ->filter()
            ->values()
            ->all();

        $operators = $this->linkedOperatorNames($businessId, $shiftIds);
        $payments = $this->paymentDetails($settlement->id, $businessId);
        $locationName = $this->locationName((int) ($settlement->location_id ?? 0), $businessId);

        // Saved snapshot rows are the Settlement SW source of truth. Build the
        // display labels once and use the same rows for View + Print; do not
        // re-query live shift sales when printing a settled document.
        $pumpMap = $this->simpleLabelMap('pumps', $settlement->lines->pluck('pump_id')->all(), $businessId, ['pump_no', 'pump_name']);
        $productIds = $settlement->lines->pluck('product_id')
            ->merge($settlement->otherSales->pluck('product_id'))
            ->merge($settlement->otherIncome->pluck('product_id'))
            ->merge($settlement->creditSales->pluck('product_id'))
            ->filter()->all();
        $productMap = $this->simpleLabelMap('products', $productIds, $businessId, ['name', 'sku']);
        $storeMap = $this->simpleLabelMap('stores', $settlement->otherSales->pluck('store_id')->all(), $businessId, ['name']);
        $creditCustomerMap = $this->contactMap($settlement->creditSales->pluck('contact_id')->all(), $businessId);

        return view('sw::settlements.show', [
            'settlement' => $settlement,
            'operators' => $operators,
            'payments' => $payments,
            'location_name' => $locationName,
            'printMode' => $printMode,
            'pumpMap' => $pumpMap,
            'productMap' => $productMap,
            'storeMap' => $storeMap,
            'creditCustomerMap' => $creditCustomerMap,
        ]);
    }

    protected function linkedOperatorNames(int $businessId, array $shiftIds)
    {
        if (empty($shiftIds)
            || ! $this->liveTableExists('sw_shift_operators')
            || ! $this->liveTableExists('pump_operators')) {
            return collect();
        }

        $shiftKey = $this->firstLiveColumn('sw_shift_operators', ['sw_shift_id', 'shift_id']);
        $operatorKey = $this->firstLiveColumn('sw_shift_operators', ['pump_operator_id', 'operator_id']);

        if (! $shiftKey || ! $operatorKey
            || ! $this->liveHasColumn('pump_operators', 'id')
            || ! $this->liveHasColumn('pump_operators', 'name')) {
            return collect();
        }

        try {
            $query = DB::table('sw_shift_operators as so')
                ->join('pump_operators as po', 'po.id', '=', 'so.' . $operatorKey)
                ->whereIn('so.' . $shiftKey, $shiftIds);

            if ($this->liveHasColumn('pump_operators', 'business_id')) {
                $query->where('po.business_id', $businessId);
            }

            return $query->whereNotNull('po.name')
                ->orderBy('po.name')
                ->distinct()
                ->pluck('po.name')
                ->filter()
                ->values();
        } catch (\Throwable $e) {
            \Log::warning('SW settlement detail operator lookup skipped', [
                'settlement_shift_ids' => $shiftIds,
                'message' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    protected function paymentDetails(int $settlementId, int $businessId)
    {
        $rows = collect();

        $hasDetailedCreditSales = false;
        if ($this->liveTableExists('sw_settlement_credit_sales')
            && $this->liveHasColumn('sw_settlement_credit_sales', 'settlement_id')) {
            try {
                $hasDetailedCreditSales = DB::table('sw_settlement_credit_sales')
                    ->where('settlement_id', $settlementId)
                    ->exists();
            } catch (\Throwable $e) {
                $hasDetailedCreditSales = false;
            }
        }

        if ($this->liveTableExists('sw_collections')
            && $this->liveHasColumn('sw_collections', 'settlement_id')) {
            try {
                $collections = DB::table('sw_collections')
                    ->where('settlement_id', $settlementId)
                    ->orderBy('id')
                    ->get();

                $contactMap = $this->contactMap($collections->pluck('contact_id')->filter()->all(), $businessId);
                $accountMap = $this->accountMap($collections->pluck('account_id')->filter()->all(), $businessId);
                $expenseIds = [];

                foreach ($collections as $collection) {
                    if (isset($collection->expense_category_id) && (int) $collection->expense_category_id > 0) {
                        $expenseIds[] = (int) $collection->expense_category_id;
                    }
                    if (preg_match('/^expense_category:(\d+)$/i', trim((string) ($collection->reference ?? '')), $match)) {
                        $expenseIds[] = (int) $match[1];
                    }
                }

                $expenseMap = $this->expenseCategoryMap($expenseIds, $businessId);

                foreach ($collections as $collection) {
                    $method = strtolower(trim((string) ($collection->payment_method ?? '')));

                    // Older builds could store a generic credit_sale collection
                    // as well as the detailed sw_settlement_credit_sales rows.
                    // The detailed rows are authoritative; do not print both.
                    if ($method === 'credit_sale' && $hasDetailedCreditSales) {
                        continue;
                    }

                    $expenseId = (int) ($collection->expense_category_id ?? 0);
                    $reference = trim((string) ($collection->reference ?? ''));
                    if ($expenseId <= 0 && preg_match('/^expense_category:(\d+)$/i', $reference, $match)) {
                        $expenseId = (int) $match[1];
                        // The encoded category is implementation detail, not a useful print reference.
                        $reference = '';
                    }

                    $rows->push((object) [
                        'type' => $this->paymentLabel($method),
                        'customer' => $contactMap[(int) ($collection->contact_id ?? 0)] ?? '—',
                        'account' => $accountMap[(int) ($collection->account_id ?? 0)] ?? '—',
                        'expense_category' => $expenseMap[$expenseId] ?? '—',
                        'reference' => $reference !== '' ? $reference : '—',
                        'amount' => (float) ($collection->amount ?? 0),
                        'note' => (string) ($collection->note ?? ''),
                        'sort_id' => (int) ($collection->id ?? 0),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('SW settlement detail collection lookup skipped', [
                    'settlement_id' => $settlementId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Detailed Credit Sales are intentionally stored outside sw_collections.
        // Add them so "all payment details" really includes every payment type.
        if ($this->liveTableExists('sw_settlement_credit_sales')
            && $this->liveHasColumn('sw_settlement_credit_sales', 'settlement_id')) {
            try {
                $creditRows = DB::table('sw_settlement_credit_sales')
                    ->where('settlement_id', $settlementId)
                    ->orderBy('id')
                    ->get();

                $creditContactMap = $this->contactMap($creditRows->pluck('contact_id')->filter()->all(), $businessId);

                foreach ($creditRows as $credit) {
                    $reference = trim((string) ($credit->order_no ?? $credit->reference ?? ''));
                    if ($reference === '' && ! empty($credit->vehicle_no)) {
                        $reference = 'Vehicle: ' . $credit->vehicle_no;
                    }

                    $rows->push((object) [
                        'type' => 'Credit Sales',
                        'customer' => $creditContactMap[(int) ($credit->contact_id ?? 0)] ?? '—',
                        'account' => 'Accounts Receivable',
                        'expense_category' => '—',
                        'reference' => $reference !== '' ? $reference : '—',
                        'amount' => (float) ($credit->amount ?? 0),
                        'note' => (string) ($credit->note ?? ''),
                        'sort_id' => 1000000000 + (int) ($credit->id ?? 0),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('SW settlement detail credit sale lookup skipped', [
                    'settlement_id' => $settlementId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $rows->sortBy('sort_id')->values();
    }

    protected function simpleLabelMap(string $table, array $ids, int $businessId, array $labelColumns): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids) || ! $this->liveTableExists($table) || ! $this->liveHasColumn($table, 'id')) {
            return [];
        }

        $available = array_values(array_filter($labelColumns, fn ($column) => $this->liveHasColumn($table, $column)));
        if (empty($available)) {
            return [];
        }

        try {
            $query = DB::table($table)->whereIn('id', $ids);
            if ($this->liveHasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            $rows = $query->get(array_merge(['id'], $available));

            return $rows->mapWithKeys(function ($row) use ($available, $table) {
                $parts = [];
                foreach ($available as $column) {
                    $value = trim((string) ($row->{$column} ?? ''));
                    if ($value !== '' && ! in_array($value, $parts, true)) {
                        $parts[] = $value;
                    }
                }
                $label = implode(' · ', $parts);
                return [(int) $row->id => ($label !== '' ? $label : ucfirst($table) . ' #' . $row->id)];
            })->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function contactMap(array $ids, int $businessId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids) || ! $this->liveTableExists('contacts')
            || ! $this->liveHasColumn('contacts', 'id')) {
            return [];
        }

        try {
            $select = ['id'];
            if ($this->liveHasColumn('contacts', 'name')) {
                $select[] = 'name';
            }
            if ($this->liveHasColumn('contacts', 'supplier_business_name')) {
                $select[] = 'supplier_business_name';
            }

            $query = DB::table('contacts')->whereIn('id', $ids);
            if ($this->liveHasColumn('contacts', 'business_id')) {
                $query->where('business_id', $businessId);
            }

            return $query->get($select)->mapWithKeys(function ($row) {
                $name = trim((string) ($row->name ?? ''));
                if ($name === '') {
                    $name = trim((string) ($row->supplier_business_name ?? ''));
                }
                return [(int) $row->id => ($name !== '' ? $name : ('Contact #' . $row->id))];
            })->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function accountMap(array $ids, int $businessId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids) || ! $this->liveTableExists('accounts')
            || ! $this->liveHasColumn('accounts', 'id')
            || ! $this->liveHasColumn('accounts', 'name')) {
            return [];
        }

        try {
            $query = DB::table('accounts')->whereIn('id', $ids);
            if ($this->liveHasColumn('accounts', 'business_id')) {
                $query->where('business_id', $businessId);
            }

            $select = ['id', 'name'];
            if ($this->liveHasColumn('accounts', 'account_number')) {
                $select[] = 'account_number';
            }

            return $query->get($select)->mapWithKeys(function ($row) {
                $name = trim((string) $row->name);
                $number = trim((string) ($row->account_number ?? ''));
                return [(int) $row->id => $name . ($number !== '' ? ' · ' . $number : '')];
            })->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function expenseCategoryMap(array $ids, int $businessId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        foreach (['expnew_categories', 'expense_categories'] as $table) {
            if (! $this->liveTableExists($table)
                || ! $this->liveHasColumn($table, 'id')
                || ! $this->liveHasColumn($table, 'name')) {
                continue;
            }

            try {
                $query = DB::table($table)->whereIn('id', $ids);
                if ($this->liveHasColumn($table, 'business_id')) {
                    $query->where('business_id', $businessId);
                }
                return $query->pluck('name', 'id')->mapWithKeys(
                    static fn ($name, $id) => [(int) $id => (string) $name]
                )->all();
            } catch (\Throwable $e) {
                // Try the legacy source below.
            }
        }

        return [];
    }

    protected function locationName(int $locationId, int $businessId): string
    {
        if ($locationId <= 0 || ! $this->liveTableExists('business_locations')
            || ! $this->liveHasColumn('business_locations', 'id')
            || ! $this->liveHasColumn('business_locations', 'name')) {
            return '—';
        }

        try {
            $query = DB::table('business_locations')->where('id', $locationId);
            if ($this->liveHasColumn('business_locations', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            return (string) ($query->value('name') ?: '—');
        } catch (\Throwable $e) {
            return '—';
        }
    }

    protected function paymentLabel(string $method): string
    {
        return [
            'cash' => 'Cash',
            'cash_deposit' => 'Cash Deposit',
            'card' => 'Cards',
            'cheque' => 'Cheques',
            'expense' => 'Expenses',
            'shortage' => 'Shortage',
            'excess' => 'Excess',
            'credit_sale' => 'Credit Sales',
            'loan_payment' => 'Loan Payments',
            'owners_drawing' => "Owner's Drawings",
            'loan_to_customer' => 'Loan to Customer',
        ][$method] ?? ucwords(str_replace('_', ' ', $method ?: 'Payment'));
    }

    protected function liveTableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT 1 FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    protected function liveHasColumn(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function firstLiveColumn(string $table, array $columns): ?string
    {
        if (! $this->liveTableExists($table)) {
            return null;
        }

        foreach ($columns as $column) {
            if ($this->liveHasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
