<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Services\PaymentSummaryService;
use Modules\SW\Services\ExpenseCategoryLookupService;

/**
 * The Payments summary and its account lists — 8048.
 */
class PaymentSummaryController extends Controller
{
    public function __construct(
        protected PaymentSummaryService $summary,
        protected ExpenseCategoryLookupService $expenseCategoryLookup
    ) {
    }

    protected function businessId(): int
    {
        // Multi-business rule: the business selected in the current session is
        // authoritative. auth()->user()->business_id is only a final fallback.
        foreach ([
            session('user.business_id'),
            session('business.id'),
            session('business_id'),
            optional(auth()->user())->business_id,
        ] as $candidate) {
            $businessId = (int) $candidate;
            if ($businessId > 0) {
                return $businessId;
            }
        }

        return 0;
    }

    /**
     * Resolve the business exactly the way Expenses New does.
     *
     * Expenses New is multi-business and its BusinessScope deliberately gives
     * the selected session business precedence over auth()->user()->business_id.
     * Using the user's default business here can make a category save correctly
     * in Expenses New and still appear "missing" in SW Settlement.
     */
    protected function expenseCategoryBusinessId(?Request $request = null): int
    {
        $session = $request && $request->hasSession() ? $request->session() : null;

        $candidates = [
            $session?->get('user.business_id'),
            $session?->get('business.id'),
            $session?->get('business_id'),
            session('user.business_id'),
            session('business.id'),
            session('business_id'),
            optional(auth()->user())->business_id,
        ];

        foreach ($candidates as $candidate) {
            $businessId = (int) $candidate;
            if ($businessId > 0) {
                return $businessId;
            }
        }

        return 0;
    }

    public function summary(Request $request)
    {
        $businessId = $this->businessId();

        $shiftIds = array_filter(array_map('intval', (array) $request->input('shift_ids', [])));

        if ($businessId <= 0) {
            return response()->json([]);
        }

        return response()->json($this->summary->summary(
            $businessId,
            (int) $request->input('pump_operator_id'),
            $shiftIds
        ));
    }

    /**
     * Live Expense Category feed for the settlement Expenses selector.
     *
     * IS2250: this is intentionally a dedicated endpoint instead of piggy-
     * backing on the Finance-account lookup. Categories may be created while
     * the settlement page remains open, so every request reads the tenant DB
     * directly and is returned with no-store headers.
     */
    public function expenseCategories(Request $request)
    {
        /*
         | IS2267 - Expenses New is a standalone module. Its Categories / Add
         | form writes expnew_categories, NOT the legacy expense_categories
         | table. Previous fixes refreshed the wrong table perfectly, so the
         | newly-added Expenses New category could never appear here.
         |
         | ExpenseCategoryLookupService reads expnew_categories as the
         | authoritative source and only falls back to expense_categories when
         | Expenses New is genuinely not installed in that tenant.
        */
        $businessId = $this->expenseCategoryBusinessId($request);
        $rows = $this->expenseCategoryLookup->rows(
            $businessId,
            (string) $request->input('q', '')
        );

        return response()->json($rows)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Live tenant-connection metadata checks.
     *
     * Laravel's Schema facade may retain metadata from a connection that was
     * active before the tenant switch. The live Expense Category feed must
     * inspect the database that is actually selected for this request.
     */
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
                'SELECT 1 AS present FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Related Finance accounts for one settlement payment type.
     *
     * IS2230: the old endpoint returned every account in the business.  Besides
     * making the dropdown difficult to use, it allowed a Card to be assigned to
     * an expense account, a Cheque to a stock account, and so on.
     *
     * Existing Finance classifications are authoritative:
     *   Cash Deposit -> Bank Account group
     *   Cards        -> Card group (never Own Cards)
     *   Cheques      -> Cheques in Hand group
     *   Expenses     -> Expenses account type / CPC group
     *   Owner Draw   -> drawing accounts, then Equity as a legacy fallback
     */
    public function accounts(Request $request)
    {
        $businessId = $this->businessId();
        $type = strtolower(trim((string) $request->input('payment_type')));

        if ($businessId <= 0) {
            return response()->json([]);
        }

        /*
         * IS2245: Expense Categories can be added while the settlement page is
         * already open. Reuse this existing AJAX endpoint so the Expenses button
         * can refresh categories immediately without a new route/cache rebuild.
         */
        if ($type === 'expense_categories') {
            // Backwards compatibility for any browser that still has the
            // pre-IS2250 settlement JavaScript cached.
            return $this->expenseCategories($request);
        }

        if (! Schema::hasTable('accounts')) {
            return response()->json([]);
        }

        $query = $this->openAccountQuery($businessId);

        // Compatibility with a cached pre-IS2230 page: no payment_type means
        // the old all-accounts response. The new page always sends a type.
        if ($type !== '') {
            $ids = $this->relatedAccountIds($businessId, $type);

            if (empty($ids)) {
                return response()->json([]);
            }

            $query->whereIn('accounts.id', $ids);
        }

        return response()->json(
            $query->orderBy('accounts.name')
                ->get(['accounts.id', 'accounts.name', 'accounts.account_number'])
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'text' => trim($a->name . ($a->account_number ? '  ·  ' . $a->account_number : '')),
                ])
                ->values()
                ->all()
        );
    }

    protected function relatedAccountIds(int $businessId, string $type): array
    {
        if ($type === 'cash_deposit') {
            $ids = $this->idsFromGroups($businessId, ['Bank Account', 'Bank']);
            return $ids ?: $this->idsFromAccountNames($businessId, ['%Bank%']);
        }

        if ($type === 'card') {
            $ids = $this->idsFromGroups($businessId, ['Card']);

            if (empty($ids)) {
                $ids = $this->idsFromAccountNames($businessId, ['%Card%']);
            }

            return $this->withoutOwnCards($businessId, $ids);
        }

        if ($type === 'cheque') {
            $ids = $this->idsFromGroups($businessId, [
                "Cheques in Hand (Customer's)",
                'Cheques in Hand',
                'Cheque in Hand',
            ]);

            return $ids ?: $this->idsFromAccountNames($businessId, ['%Cheque%Hand%', '%Cheque%']);
        }

        if ($type === 'expense') {
            // Match the application's normal Expense form: Expenses account
            // type plus the legacy CPC account group.
            $ids = array_merge(
                $this->idsFromAccountTypes($businessId, ['Expenses']),
                $this->idsFromGroups($businessId, ['CPC', 'Expenses', 'Expense'])
            );
            $ids = array_values(array_unique(array_map('intval', $ids)));

            return $ids ?: $this->idsFromAccountNames($businessId, ['%Expense%']);
        }

        if ($type === 'loan_payment') {
            /*
             * IS2233: Loan Payment was the only Finance-posted payment type
             * whose UI had no account source.  Follow the established Petro
             * settlement convention: prefer accounts in the Loans Given group,
             * then legacy loan groups/names. Never expose unrelated accounts.
             */
            $ids = $this->idsFromGroups($businessId, ['Loans Given']);

            if (empty($ids)) {
                $ids = $this->idsFromGroups($businessId, ['Loan', 'Loans']);
            }

            return $ids ?: $this->idsFromAccountNames($businessId, ['%Loan%']);
        }

        if ($type === 'owners_drawing') {
            $ids = array_merge(
                $this->idsFromGroups($businessId, ['Owner Drawings', 'Owner Drawing', 'Drawings']),
                $this->idsFromAccountNames($businessId, ['%Owner%Drawing%', '%Drawing%'])
            );
            $ids = array_values(array_unique(array_map('intval', $ids)));

            // Older databases often classify drawings only as Equity.
            if (empty($ids)) {
                $ids = $this->idsFromAccountTypes($businessId, ['Equity']);
            }

            return $this->withoutOwnCards($businessId, $ids);
        }

        return [];
    }

    protected function openAccountQuery(int $businessId)
    {
        return DB::table('accounts')
            ->where('accounts.business_id', $businessId)
            ->when(
                Schema::hasColumn('accounts', 'deleted_at'),
                fn ($q) => $q->whereNull('accounts.deleted_at')
            )
            ->when(
                Schema::hasColumn('accounts', 'is_closed'),
                fn ($q) => $q->where('accounts.is_closed', 0)
            );
    }

    protected function idsFromGroups(int $businessId, array $names): array
    {
        if (! Schema::hasTable('account_groups')
            || ! Schema::hasColumn('accounts', 'asset_type')) {
            return [];
        }

        $names = array_values(array_unique(array_map(
            static fn ($name) => strtolower(trim((string) $name)),
            $names
        )));

        $query = DB::table('accounts')
            ->join('account_groups', 'account_groups.id', '=', 'accounts.asset_type')
            ->where('accounts.business_id', $businessId)
            ->whereIn(DB::raw('LOWER(TRIM(account_groups.name))'), $names)
            ->when(
                Schema::hasColumn('account_groups', 'business_id'),
                fn ($q) => $q->where('account_groups.business_id', $businessId)
            )
            ->when(
                Schema::hasColumn('accounts', 'deleted_at'),
                fn ($q) => $q->whereNull('accounts.deleted_at')
            )
            ->when(
                Schema::hasColumn('accounts', 'is_closed'),
                fn ($q) => $q->where('accounts.is_closed', 0)
            );

        return $query->pluck('accounts.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function idsFromAccountTypes(int $businessId, array $names): array
    {
        if (! Schema::hasTable('account_types')
            || ! Schema::hasColumn('accounts', 'account_type_id')) {
            return [];
        }

        $names = array_values(array_unique(array_map(
            static fn ($name) => strtolower(trim((string) $name)),
            $names
        )));

        $query = DB::table('accounts')
            ->join('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->where('accounts.business_id', $businessId)
            ->whereIn(DB::raw('LOWER(TRIM(account_types.name))'), $names)
            ->when(
                Schema::hasColumn('account_types', 'business_id'),
                fn ($q) => $q->where('account_types.business_id', $businessId)
            )
            ->when(
                Schema::hasColumn('accounts', 'deleted_at'),
                fn ($q) => $q->whereNull('accounts.deleted_at')
            )
            ->when(
                Schema::hasColumn('accounts', 'is_closed'),
                fn ($q) => $q->where('accounts.is_closed', 0)
            );

        return $query->pluck('accounts.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function idsFromAccountNames(int $businessId, array $patterns): array
    {
        $query = $this->openAccountQuery($businessId)
            ->where(function ($q) use ($patterns) {
                foreach ($patterns as $index => $pattern) {
                    if ($index === 0) {
                        $q->where('accounts.name', 'like', $pattern);
                    } else {
                        $q->orWhere('accounts.name', 'like', $pattern);
                    }
                }
            });

        return $query->pluck('accounts.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function withoutOwnCards(int $businessId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        $query = $this->openAccountQuery($businessId)
            ->whereIn('accounts.id', $ids)
            ->whereRaw('LOWER(accounts.name) NOT LIKE ?', ['%own card%']);

        if (Schema::hasTable('account_groups')
            && Schema::hasColumn('accounts', 'asset_type')) {
            $query->leftJoin('account_groups as own_card_group', 'own_card_group.id', '=', 'accounts.asset_type')
                ->where(function ($q) {
                    $q->whereNull('own_card_group.id')
                        ->orWhereRaw('LOWER(TRIM(own_card_group.name)) <> ?', ['own cards']);
                });
        }

        return $query->pluck('accounts.id')->map(fn ($id) => (int) $id)->all();
    }
}
