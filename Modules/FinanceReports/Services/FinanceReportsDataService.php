<?php

namespace Modules\FinanceReports\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 |==============================================================================
 | PERFORMANCE NOTE - why the date filters are written as ranges
 |==============================================================================
 |
 | Every report here filters account_transactions.operation_date. Those filters
 | used whereDate(), which compiles to
 |
 |     DATE(operation_date) >= '2026-08-01'
 |
 | Wrapping the column in a function makes the comparison unindexable: MySQL has
 | to read and convert every row before it can decide, so each report scanned the
 | whole table. account_transactions is one of the largest tables in this system.
 |
 | operation_date is a dateTime, so the equivalent range is
 |
 |     operation_date >= '2026-08-01 00:00:00'
 |     operation_date <  '2026-09-01 00:00:00'      (end date + 1 day)
 |
 | which reads an index directly. The end bound is exclusive and shifted forward
 | a day on purpose: '<= 2026-08-31' would silently drop everything timestamped
 | later than midnight on the closing day, which whereDate() did include.
 |
 | The indexes themselves are added by
 |     FinanceReports_performance_indexes.sql
 | Without that script these queries are still correct, and still faster than
 | before, but the index is where most of the gain is.
 */
class FinanceReportsDataService
{
    /*
     |--------------------------------------------------------------------------
     | Schema checks, asked once per request instead of once per use.
     |--------------------------------------------------------------------------
     |
     | This class guards nearly every query with self::tableExists() or
     | hasColumn(), which is right - tenant databases here are not always fully
     | migrated, and a missing column must not take a report down. But each of
     | those calls is a real query to the database about its own structure, and
     | there are 82 of them. On a report that asks about `transactions` twenty-two
     | times, twenty-one of those round trips tell us what we already knew.
     |
     | The schema cannot change while a request is being served, so the answer is
     | cached in memory for the life of the request. Behaviour is identical; only
     | the number of round trips changes.
     |
     | Static rather than per-instance because the service is resolved more than
     | once on the dashboard pages, and the schema is the same for all of them.
     */
    private static array $schemaTableCache = [];
    private static array $schemaColumnCache = [];

    /*
     |--------------------------------------------------------------------------
     | Date bounds for an indexable comparison.
     |--------------------------------------------------------------------------
     |
     | operation_date is a dateTime. Filtering it with whereDate() compiles to
     | DATE(operation_date) >= '...', and wrapping the column in a function makes
     | the comparison unindexable - every row has to be read and converted. These
     | two produce the equivalent range, which an index can serve.
     |
     | They exist as methods rather than inline string concatenation because the
     | inline version was fragile: appending ' 00:00:00' to a value that already
     | carried a time produced '2026-08-01 00:00:00 00:00:00', which MySQL cannot
     | read and which silently matches NOTHING. Every caller here passes Y-m-d
     | today, but a caller that did not would empty the report with no error.
     | Normalising first removes that possibility.
     */
    private static function dayStart(string $date): ?string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? null : date('Y-m-d 00:00:00', $timestamp);
    }

    /**
     * Start of the day AFTER the given date, for use with a strict '<'.
     *
     * Exclusive on purpose: '<= 2026-08-31' would drop everything timestamped
     * later than midnight on the closing day, which whereDate() included. This
     * keeps the closing day whole.
     */
    private static function dayAfter(string $date): ?string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? null : date('Y-m-d 00:00:00', strtotime('+1 day', $timestamp));
    }

    private static function tableExists(string $table): bool
    {
        if (! array_key_exists($table, self::$schemaTableCache)) {
            self::$schemaTableCache[$table] = Schema::hasTable($table);
        }

        return self::$schemaTableCache[$table];
    }

    private static function columnExists(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (! array_key_exists($key, self::$schemaColumnCache)) {
            // A column on a table that does not exist would otherwise raise.
            self::$schemaColumnCache[$key] = self::tableExists($table)
                && Schema::hasColumn($table, $column);
        }

        return self::$schemaColumnCache[$key];
    }

    public function locations(int $business_id): Collection
    {
        if (!self::tableExists('business_locations')) {
            return collect();
        }

        return DB::table('business_locations')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function trialBalance(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        /*
         * A Trial Balance is a statement of ACCOUNT BALANCES at a point in time,
         * not a list of gross debit/credit turnover for an arbitrary date range.
         * The previous implementation summed period debit and credit movements
         * separately, so an account with both sides posted showed turnover rather
         * than its closing balance and could never agree with List Accounts / the
         * Account Book closing balance.
         *
         * Keep the public method signature for route/export compatibility, but use
         * the selected closing date as the "as at" date. Older callers that only
         * supplied start still remain usable by treating it as that date.
         */
        $asAt = $end ?: $start;

        $rows = $this->accountTotals($business_id, null, $asAt, $location_id)
            ->map(function ($r) {
                $net = round((float)$r->debit - (float)$r->credit, 4);

                // A debit balance belongs in Debit; a credit balance belongs in
                // Credit. Abnormal balances are therefore shown on the opposite
                // side naturally instead of being forced by account class.
                $r->debit = $net > 0 ? $net : 0.0;
                $r->credit = $net < 0 ? abs($net) : 0.0;
                $r->closing_balance = $net;

                return $r;
            })
            ->filter(fn ($r) => abs((float)$r->debit) > 0.0001 || abs((float)$r->credit) > 0.0001)
            ->values();

        $totalDebit = round($rows->sum('debit'), 4);
        $totalCredit = round($rows->sum('credit'), 4);

        return [
            'as_at' => $asAt,
            'rows' => $rows,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'difference' => round($totalDebit - $totalCredit, 4),
            ],
        ];
    }

    public function balanceSheet(int $business_id, ?string $as_at, $location_id = null): array
    {
        // Read the ledger ONCE. Besides being faster on large tenant databases,
        // using one snapshot guarantees the Balance Sheet rows and the unclosed
        // earnings line are calculated from precisely the same scoped postings.
        $allRows = $this->accountTotals($business_id, null, $as_at, $location_id)
            ->map(function ($r) {
                $category = $this->accountCategory($r);
                $normal = $this->normalSide($r);
                $balance = $normal === 'credit' ? ((float)$r->credit - (float)$r->debit) : ((float)$r->debit - (float)$r->credit);
                $r->category = $category;
                $r->balance = round($balance, 4);
                $showOnBalanceSheet = (int)($r->show_in_balance_sheet ?? 1) !== 0;
                $r->section = $showOnBalanceSheet && in_array($category, ['Assets', 'Liabilities', 'Equity'], true)
                    ? $category
                    : 'Other';
                $r->is_current = $showOnBalanceSheet && $this->isCurrentBalanceSheetAccount($r);
                return $r;
            });

        $rows = $allRows
            ->filter(fn ($r) => $r->section !== 'Other' && abs((float)$r->balance) > 0.0001)
            ->values();

        $assets = $rows->where('section', 'Assets')->values();
        $liabilities = $rows->where('section', 'Liabilities')->values();
        $equity = $rows->where('section', 'Equity')->values();

        /*
         * Income and expense accounts are temporary accounts. Until they are
         * formally closed to retained earnings, their cumulative net result is
         * part of equity and MUST be reflected in the Balance Sheet. Leaving it
         * out made Assets - (Liabilities + Equity) differ by exactly the unclosed
         * profit/loss in many tenants.
         *
         * Summing credit - debit across BOTH Income and Expense accounts gives
         * cumulative unclosed profit directly: Income normally contributes a
         * positive credit balance, while Expenses normally contribute a negative
         * debit balance. If prior periods were closed, their closing entries have
         * already zeroed those temporary accounts and therefore contribute zero.
         */
        $currentEarnings = round(
            $allRows
                ->filter(fn ($r) => in_array($r->category, ['Income', 'Expenses'], true))
                ->sum(fn ($r) => (float)$r->credit - (float)$r->debit),
            4
        );
        if (abs($currentEarnings) > 0.0001) {
            $equity->push((object) [
                'id' => null,
                'account_number' => '',
                'name' => 'Current / Unclosed Earnings (Loss)',
                'account_type_name' => 'Equity',
                'account_group_name' => '',
                'section' => 'Equity',
                'is_current' => false,
                'balance' => $currentEarnings,
                'synthetic' => true,
            ]);
        }

        $currentAssets = $assets->where('is_current', true)->values();
        $currentLiabilities = $liabilities->where('is_current', true)->values();

        $totalAssets = round($assets->sum('balance'), 4);
        $totalLiabilities = round($liabilities->sum('balance'), 4);
        $totalEquity = round($equity->sum('balance'), 4);
        $totalCurrentAssets = round($currentAssets->sum('balance'), 4);
        $totalCurrentLiabilities = round($currentLiabilities->sum('balance'), 4);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
                'current_earnings' => $currentEarnings,
                'current_assets' => $totalCurrentAssets,
                'current_liabilities' => $totalCurrentLiabilities,
                'working_capital' => round($totalCurrentAssets - $totalCurrentLiabilities, 4),
                'current_ratio' => abs($totalCurrentLiabilities) > 0.0001
                    ? round($totalCurrentAssets / $totalCurrentLiabilities, 4)
                    : null,
                'difference' => round($totalAssets - ($totalLiabilities + $totalEquity), 4),
            ],
        ];
    }

    public function incomeStatement(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $rows = $this->accountTotals($business_id, $start, $end, $location_id)
            ->map(function ($r) {
                $section = $this->plSection($r);
                $r->section = $section;
                $r->amount = $section === 'Income'
                    ? round((float)$r->credit - (float)$r->debit, 4)
                    : round((float)$r->debit - (float)$r->credit, 4);
                $r->is_cogs = $section === 'Expenses' && $this->isCogsAccount($r);
                $r->is_revenue = $section === 'Income' && $this->isRevenueAccount($r);
                return $r;
            })
            ->filter(fn ($r) => in_array($r->section, ['Income', 'Expenses'], true) && abs((float)$r->amount) > 0.0001)
            ->values();

        $income = $rows->where('section', 'Income')->values();
        $expenses = $rows->where('section', 'Expenses')->values();
        $cogs = $expenses->where('is_cogs', true)->values();
        $revenue = $income->where('is_revenue', true)->values();

        $totalIncome = round($income->sum('amount'), 4);
        $totalExpenses = round($expenses->sum('amount'), 4);
        $totalCogs = round($cogs->sum('amount'), 4);

        // Gross profit belongs to SALES revenue less COGS. Do not inflate it with
        // interest/gain/other-income accounts. Older charts sometimes have only a
        // generic Income type and no Sales Income group; in that legacy case fall
        // back to total income rather than returning a meaningless zero revenue.
        $totalRevenue = $revenue->isNotEmpty()
            ? round($revenue->sum('amount'), 4)
            : $totalIncome;

        /*
         * IS2216: Direct/Petro settlement "Other Income" rows are saved as
         * sell-lines on the settlement sale transaction and the legacy posting
         * path therefore credits Sales Income. The source table still identifies
         * those rows explicitly as other_incomes. Reclassify that source amount
         * from operating revenue to Other Income without changing total income.
         *
         * This is a presentation/classification correction only; the underlying
         * accounting entry remains the authoritative ledger source.
         */
        $settlementOtherIncome = $this->settlementOtherIncomeTotal(
            $business_id,
            $start,
            $end,
            $location_id
        );

        if (abs($settlementOtherIncome) > 0.0001) {
            $totalRevenue = round($totalRevenue - $settlementOtherIncome, 4);
        }

        $otherIncome = round($totalIncome - $totalRevenue, 4);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'totals' => [
                'income' => $totalIncome,
                'revenue' => $totalRevenue,
                'other_income' => $otherIncome,
                'expenses' => $totalExpenses,
                'cogs' => $totalCogs,
                'gross_profit' => round($totalRevenue - $totalCogs, 4),
                'operating_expenses' => round($totalExpenses - $totalCogs, 4),
                'net_profit' => round($totalIncome - $totalExpenses, 4),
            ],
        ];
    }

    /**
     * Amount entered through settlement "Other Income" rows for the selected
     * period/location. These rows are linked to the finalized sale transaction
     * through other_incomes.transaction_id.
     */
    private function settlementOtherIncomeTotal(
        int $business_id,
        ?string $start,
        ?string $end,
        $location_id = null
    ): float {
        if (! self::tableExists('other_incomes')
            || ! self::tableExists('transactions')
            || ! self::columnExists('other_incomes', 'transaction_id')) {
            return 0.0;
        }

        $amountColumn = self::columnExists('other_incomes', 'sub_total')
            ? 'sub_total'
            : (self::columnExists('other_incomes', 'amount') ? 'amount' : null);

        if ($amountColumn === null) {
            return 0.0;
        }

        $query = DB::table('other_incomes as fr_other_income')
            ->join('transactions as fr_other_income_tx', 'fr_other_income_tx.id', '=', 'fr_other_income.transaction_id')
            ->where('fr_other_income_tx.business_id', $business_id);

        if (self::columnExists('transactions', 'type')) {
            $query->where('fr_other_income_tx.type', 'sell');
        }
        if (self::columnExists('transactions', 'sub_type')) {
            $query->where('fr_other_income_tx.sub_type', 'settlement');
        }
        if (self::columnExists('transactions', 'status')) {
            $query->where('fr_other_income_tx.status', 'final');
        }

        if ($start) {
            $query->where('fr_other_income_tx.transaction_date', '>=', self::dayStart($start));
        }
        if ($end) {
            $query->where('fr_other_income_tx.transaction_date', '<', self::dayAfter($end));
        }

        if ($location_id) {
            if (self::columnExists('transactions', 'location_id')) {
                $query->where('fr_other_income_tx.location_id', $location_id);
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $query->where('fr_other_income_tx.business_location_id', $location_id);
            } elseif (self::columnExists('other_incomes', 'location_id')) {
                $query->where('fr_other_income.location_id', $location_id);
            } else {
                // Never turn a branch report into a business-wide total.
                return 0.0;
            }
        }

        if (self::columnExists('other_incomes', 'deleted_at')) {
            $query->whereNull('fr_other_income.deleted_at');
        }
        if (self::columnExists('transactions', 'deleted_at')) {
            $query->whereNull('fr_other_income_tx.deleted_at');
        }
        if (self::columnExists('transactions', 'new_deleted_at')) {
            $query->whereNull('fr_other_income_tx.new_deleted_at');
        }

        return round((float) $query->sum('fr_other_income.' . $amountColumn), 4);
    }

    public function accountLedger(int $business_id, int $account_id, ?string $start, ?string $end, $location_id = null): array
    {
        $opening = $this->transactionsQuery($business_id, null, $start ? Carbon::parse($start)->subDay()->format('Y-m-d') : null, $location_id)
            ->where('account_transactions.account_id', $account_id)
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'debit' THEN account_transactions.amount ELSE 0 END) as debit")
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'credit' THEN account_transactions.amount ELSE 0 END) as credit")
            ->first();

        $running = round(((float)($opening->debit ?? 0)) - ((float)($opening->credit ?? 0)), 4);

        $transactions = $this->transactionsQuery($business_id, $start, $end, $location_id)
            ->where('account_transactions.account_id', $account_id)
            ->select('account_transactions.*')
            ->orderBy('account_transactions.operation_date')
            ->orderBy('account_transactions.id')
            ->get()
            ->map(function ($t) use (&$running) {
                $debit = $t->type === 'debit' ? (float)$t->amount : 0;
                $credit = $t->type === 'credit' ? (float)$t->amount : 0;
                $running += $debit - $credit;
                $t->debit = round($debit, 4);
                $t->credit = round($credit, 4);
                $t->running_balance = round($running, 4);
                return $t;
            });

        return [
            'opening_balance' => round(((float)($opening->debit ?? 0)) - ((float)($opening->credit ?? 0)), 4),
            'rows' => $transactions,
            'closing_balance' => $running,
        ];
    }


    public function generalLedger(int $business_id, ?string $start, ?string $end, $location_id = null, ?int $account_id = null): array
    {
        $accounts = DB::table('accounts')
            ->where('business_id', $business_id)
            ->whereNull('deleted_at')
            ->when($account_id, fn ($q) => $q->where('id', $account_id))
            ->orderBy('name')
            ->get(['id', 'name', 'account_number']);

        $ledgers = $accounts->map(function ($account) use ($business_id, $start, $end, $location_id) {
            $ledger = $this->accountLedger($business_id, (int)$account->id, $start, $end, $location_id);
            return (object) [
                'account' => $account,
                'opening_balance' => $ledger['opening_balance'],
                'rows' => $ledger['rows'],
                'closing_balance' => $ledger['closing_balance'],
                'debit' => $ledger['rows']->sum('debit'),
                'credit' => $ledger['rows']->sum('credit'),
            ];
        })->filter(fn ($l) => abs($l->opening_balance) > 0.0001 || abs($l->closing_balance) > 0.0001 || $l->rows->count() > 0)->values();

        return [
            'ledgers' => $ledgers,
            'totals' => [
                'debit' => round($ledgers->sum('debit'), 4),
                'credit' => round($ledgers->sum('credit'), 4),
                'accounts' => $ledgers->count(),
            ],
        ];
    }

    public function bookByKeyword(int $business_id, ?string $start, ?string $end, $location_id = null, array $keywords = []): array
    {
        $accounts = $this->accountsByPurpose($business_id, $keywords);

        $rows = collect();
        foreach ($accounts as $account) {
            $ledger = $this->accountLedger($business_id, (int)$account->id, $start, $end, $location_id);
            foreach ($ledger['rows'] as $row) {
                $row->account_name = $account->name;
                $row->account_number = $account->account_number;
                $rows->push($row);
            }
        }

        $rows = $rows->sortBy([['operation_date', 'asc'], ['id', 'asc']])->values();

        return [
            'accounts' => $accounts,
            'rows' => $rows,
            'totals' => [
                'debit' => round($rows->sum('debit'), 4),
                'credit' => round($rows->sum('credit'), 4),
                'net' => round($rows->sum('debit') - $rows->sum('credit'), 4),
                'records' => $rows->count(),
            ],
        ];
    }

    public function journalRegister(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $query = $this->transactionsQuery($business_id, $start, $end, $location_id)
            ->leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->select('account_transactions.*', 'accounts.name as account_name', 'accounts.account_number')
            ->orderBy('account_transactions.operation_date')
            ->orderBy('account_transactions.id');

        $rows = $query->get()->map(function ($r) {
            $r->debit = $r->type === 'debit' ? round((float)$r->amount, 4) : 0;
            $r->credit = $r->type === 'credit' ? round((float)$r->amount, 4) : 0;
            $r->voucher_no = $r->sub_type ?? $r->type ?? '';
            return $r;
        });

        return [
            'rows' => $rows,
            'totals' => [
                'debit' => round($rows->sum('debit'), 4),
                'credit' => round($rows->sum('credit'), 4),
                'records' => $rows->count(),
            ],
        ];
    }

    public function dayBook(int $business_id, ?string $date, $location_id = null): array
    {
        $rows = $this->transactionsQuery($business_id, $date, $date, $location_id)
            ->leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->select('account_transactions.*', 'accounts.name as account_name', 'accounts.account_number')
            ->orderBy('account_transactions.operation_date')
            ->orderBy('account_transactions.id')
            ->get()
            ->map(function ($r) {
                $r->debit = $r->type === 'debit' ? round((float)$r->amount, 4) : 0;
                $r->credit = $r->type === 'credit' ? round((float)$r->amount, 4) : 0;
                return $r;
            });

        return [
            'rows' => $rows,
            'totals' => [
                'debit' => round($rows->sum('debit'), 4),
                'credit' => round($rows->sum('credit'), 4),
                'records' => $rows->count(),
            ],
        ];
    }


    public function managementDashboard(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $pl = $this->incomeStatement($business_id, $start, $end, $location_id);
        $bs = $this->balanceSheet($business_id, $end, $location_id);
        $cash = $this->bookByKeyword($business_id, $start, $end, $location_id, ['cash']);
        $bank = $this->bookByKeyword($business_id, $start, $end, $location_id, ['bank']);

        $assets = (float)($bs['totals']['assets'] ?? 0);
        $liabilities = (float)($bs['totals']['liabilities'] ?? 0);
        $equity = (float)($bs['totals']['equity'] ?? 0);
        $currentAssets = (float)($bs['totals']['current_assets'] ?? 0);
        $currentLiabilities = (float)($bs['totals']['current_liabilities'] ?? 0);
        $income = (float)($pl['totals']['income'] ?? 0);
        $revenue = (float)($pl['totals']['revenue'] ?? $income);
        $otherIncome = (float)($pl['totals']['other_income'] ?? ($income - $revenue));
        $expenses = (float)($pl['totals']['expenses'] ?? 0);
        $net_profit = (float)($pl['totals']['net_profit'] ?? 0);

        return [
            'kpis' => [
                // Revenue is sales/operating revenue. Keep Other Income separate
                // so gains/interest do not inflate the Revenue KPI.
                'revenue' => round($revenue, 4),
                'other_income' => round($otherIncome, 4),
                'expenses' => round($expenses, 4),
                'net_profit' => round($net_profit, 4),
                'cash_movement' => round($cash['totals']['net'] ?? 0, 4),
                'bank_movement' => round($bank['totals']['net'] ?? 0, 4),
                'assets' => round($assets, 4),
                'liabilities' => round($liabilities, 4),
                'equity' => round($equity, 4),
                'current_assets' => round($currentAssets, 4),
                'current_liabilities' => round($currentLiabilities, 4),
                'working_capital' => round($currentAssets - $currentLiabilities, 4),
            ],
            'pl' => $pl,
            'bs' => $bs,
        ];
    }

    public function budgetVsActual(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $actual = $this->incomeStatement($business_id, $start, $end, $location_id);
        $rows = collect();
        foreach ($actual['income']->merge($actual['expenses']) as $row) {
            $budget = $this->budgetAmount($business_id, (int)$row->id, $start, $end, $location_id);
            $variance = round(((float)$row->amount) - $budget, 4);
            $variance_pct = abs($budget) > 0.0001 ? round(($variance / $budget) * 100, 2) : null;
            $rows->push((object) [
                'account_id' => $row->id,
                'account_name' => $row->name,
                'account_number' => $row->account_number,
                'section' => $row->section,
                'budget' => $budget,
                'actual' => round((float)$row->amount, 4),
                'variance' => $variance,
                'variance_pct' => $variance_pct,
            ]);
        }

        $incomeRows = $rows->where('section', 'Income');
        $expenseRows = $rows->where('section', 'Expenses');
        $incomeBudget = round($incomeRows->sum('budget'), 4);
        $incomeActual = round($incomeRows->sum('actual'), 4);
        $expenseBudget = round($expenseRows->sum('budget'), 4);
        $expenseActual = round($expenseRows->sum('actual'), 4);
        $netBudget = round($incomeBudget - $expenseBudget, 4);
        $netActual = round($incomeActual - $expenseActual, 4);

        return [
            'rows' => $rows,
            'has_budget_table' => self::tableExists('account_budgets') || self::tableExists('budgets'),
            'section_totals' => [
                'income' => [
                    'budget' => $incomeBudget,
                    'actual' => $incomeActual,
                    'variance' => round($incomeActual - $incomeBudget, 4),
                ],
                'expenses' => [
                    'budget' => $expenseBudget,
                    'actual' => $expenseActual,
                    'variance' => round($expenseActual - $expenseBudget, 4),
                ],
            ],
            // Do not add Income + Expenses together in the footer; that is not
            // a financial result. The top-level total is the budgeted/actual NET
            // result (Income - Expenses).
            'totals' => [
                'budget' => $netBudget,
                'actual' => $netActual,
                'variance' => round($netActual - $netBudget, 4),
                'records' => $rows->count(),
            ],
        ];
    }

    public function analysisBySection(int $business_id, ?string $start, ?string $end, $location_id = null, string $section = 'Income'): array
    {
        $pl = $this->incomeStatement($business_id, $start, $end, $location_id);
        if ($section === 'Revenue') {
            $rows = collect($pl['income'])->filter(fn ($row) => ! empty($row->is_revenue))->values();

            // Legacy charts may not carry the Sales Income default group. In
            // that case incomeStatement() deliberately falls back to total income
            // as revenue, so keep Revenue Analysis usable with the same fallback.
            if ($rows->isEmpty() && abs((float) data_get($pl, 'totals.revenue', 0)) > 0.0001) {
                $rows = collect($pl['income'])->values();
            }
        } else {
            $rows = $section === 'Income' ? collect($pl['income']) : collect($pl['expenses']);
        }

        $total = max(abs((float)$rows->sum('amount')), 0.0001);
        $rows = $rows->map(function ($row) use ($total) {
            $row->percentage = round((((float)$row->amount) / $total) * 100, 2);
            return $row;
        })->sortByDesc('amount')->values();

        return [
            'rows' => $rows,
            'totals' => [
                'amount' => round($rows->sum('amount'), 4),
                'records' => $rows->count(),
            ],
        ];
    }

    public function branchPerformance(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $locations = $this->locations($business_id);
        $rows = collect();
        $physicalBranchCount = 0;

        foreach ($locations as $id => $name) {
            if ($location_id && (string)$location_id !== (string)$id) {
                continue;
            }
            $physicalBranchCount++;
            $pl = $this->incomeStatement($business_id, $start, $end, $id);
            $bs = $this->balanceSheet($business_id, $end, $id);
            $income = (float)($pl['totals']['income'] ?? 0);
            $revenue = (float)($pl['totals']['revenue'] ?? $income);
            $expenses = (float)($pl['totals']['expenses'] ?? 0);
            $net = (float)($pl['totals']['net_profit'] ?? 0);
            $rows->push((object) [
                'location_id' => $id,
                'location_name' => $name,
                'income' => round($income, 4),
                'revenue' => round($revenue, 4),
                'expenses' => round($expenses, 4),
                'net_profit' => round($net, 4),
                'assets' => round($bs['totals']['assets'] ?? 0, 4),
                'liabilities' => round($bs['totals']['liabilities'] ?? 0, 4),
                'net_margin' => abs($revenue) > 0.0001 ? round(($net / $revenue) * 100, 2) : 0,
                'unallocated' => false,
            ]);
        }

        // In all-branch mode, reconcile branch rows to the true consolidated
        // statements. Legacy/manual entries that have no resolvable source
        // location remain visible as Unallocated/Global rather than being guessed
        // into (and duplicated across) physical branches.
        if (!$location_id) {
            $consolidatedPl = $this->incomeStatement($business_id, $start, $end, null);
            $consolidatedBs = $this->balanceSheet($business_id, $end, null);
            $target = [
                'income' => (float)($consolidatedPl['totals']['income'] ?? 0),
                'revenue' => (float)($consolidatedPl['totals']['revenue'] ?? 0),
                'expenses' => (float)($consolidatedPl['totals']['expenses'] ?? 0),
                'net_profit' => (float)($consolidatedPl['totals']['net_profit'] ?? 0),
                'assets' => (float)($consolidatedBs['totals']['assets'] ?? 0),
                'liabilities' => (float)($consolidatedBs['totals']['liabilities'] ?? 0),
            ];
            $delta = [];
            foreach ($target as $key => $value) {
                $delta[$key] = round($value - (float)$rows->sum($key), 4);
            }
            $hasUnallocated = collect($delta)->contains(fn ($value) => abs((float)$value) > 0.0001);
            if ($hasUnallocated) {
                $rows->push((object) [
                    'location_id' => null,
                    'location_name' => 'Unallocated / Global (No resolvable source location)',
                    'income' => $delta['income'],
                    'revenue' => $delta['revenue'],
                    'expenses' => $delta['expenses'],
                    'net_profit' => $delta['net_profit'],
                    'assets' => $delta['assets'],
                    'liabilities' => $delta['liabilities'],
                    'net_margin' => abs($delta['revenue']) > 0.0001 ? round(($delta['net_profit'] / $delta['revenue']) * 100, 2) : 0,
                    'unallocated' => true,
                ]);
            }
        }

        return [
            'rows' => $rows,
            'totals' => [
                'income' => round($rows->sum('income'), 4),
                'revenue' => round($rows->sum('revenue'), 4),
                'expenses' => round($rows->sum('expenses'), 4),
                'net_profit' => round($rows->sum('net_profit'), 4),
                'assets' => round($rows->sum('assets'), 4),
                'liabilities' => round($rows->sum('liabilities'), 4),
                'branches' => $physicalBranchCount,
                'has_unallocated' => $rows->contains(fn ($row) => !empty($row->unallocated)),
            ],
        ];
    }

    public function financialRatios(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $pl = $this->incomeStatement($business_id, $start, $end, $location_id);
        $bs = $this->balanceSheet($business_id, $end, $location_id);
        $income = (float)($pl['totals']['income'] ?? 0);
        $revenue = (float)($pl['totals']['revenue'] ?? $income);
        $expenses = (float)($pl['totals']['expenses'] ?? 0);
        $net = (float)($pl['totals']['net_profit'] ?? 0);
        $assets = (float)($bs['totals']['assets'] ?? 0);
        $liabilities = (float)($bs['totals']['liabilities'] ?? 0);
        $equity = (float)($bs['totals']['equity'] ?? 0);
        $currentAssets = (float)($bs['totals']['current_assets'] ?? 0);
        $currentLiabilities = (float)($bs['totals']['current_liabilities'] ?? 0);

        return [
            'rows' => collect([
                (object)['name' => 'Net Profit Margin', 'value' => $this->ratio($net, $revenue) * 100, 'suffix' => '%'],
                (object)['name' => 'Expense Ratio', 'value' => $this->ratio($expenses, $revenue) * 100, 'suffix' => '%'],
                (object)['name' => 'Debt Ratio', 'value' => $this->ratio($liabilities, $assets) * 100, 'suffix' => '%'],
                (object)['name' => 'Return on Assets (ROA)', 'value' => $this->ratio($net, $assets) * 100, 'suffix' => '%'],
                (object)['name' => 'Return on Equity (ROE)', 'value' => $this->ratio($net, $equity) * 100, 'suffix' => '%'],
                (object)['name' => 'Current Ratio', 'value' => $this->ratio($currentAssets, $currentLiabilities), 'suffix' => 'x'],
                (object)['name' => 'Working Capital', 'value' => round($currentAssets - $currentLiabilities, 4), 'suffix' => ''],
                (object)['name' => 'Assets to Liabilities', 'value' => $this->ratio($assets, $liabilities), 'suffix' => 'x'],
            ]),
            'source' => [
                'income' => $income,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_profit' => $net,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'current_assets' => $currentAssets,
                'current_liabilities' => $currentLiabilities,
            ],
        ];
    }

    public function comparativeReport(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $current = $this->incomeStatement($business_id, $start, $end, $location_id);
        $days = $start && $end ? Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1 : 30;
        $prev_end = $start ? Carbon::parse($start)->subDay()->format('Y-m-d') : now()->subMonth()->endOfMonth()->format('Y-m-d');
        $prev_start = Carbon::parse($prev_end)->subDays($days - 1)->format('Y-m-d');
        $previous = $this->incomeStatement($business_id, $prev_start, $prev_end, $location_id);

        $metrics = collect(['income' => 'Income', 'expenses' => 'Expenses', 'net_profit' => 'Net Profit'])->map(function ($label, $key) use ($current, $previous) {
            $c = (float)($current['totals'][$key] ?? 0);
            $p = (float)($previous['totals'][$key] ?? 0);
            $change = round($c - $p, 4);
            return (object) ['name' => $label, 'current' => round($c, 4), 'previous' => round($p, 4), 'change' => $change, 'change_pct' => abs($p) > 0.0001 ? round(($change / $p) * 100, 2) : null];
        })->values();

        return ['rows' => $metrics, 'previous_start' => $prev_start, 'previous_end' => $prev_end];
    }



    public function contactOutstanding(int $business_id, string $contact_type, ?string $as_at, $location_id = null): array
    {
        if (!$this->hasReceivablePayableTables()) {
            return $this->emptyContactReport('Required tables are not available.');
        }

        $direction = $contact_type === 'supplier' ? 'purchase' : 'sell';
        $rows = $this->contactBalanceRows($business_id, $contact_type, $as_at, $location_id, $direction)
            ->filter(fn ($r) => abs((float)$r->balance) > 0.0001)
            ->sortByDesc('balance')
            ->values();

        return [
            'rows' => $rows,
            'totals' => [
                'invoiced' => round($rows->sum('final_total'), 4),
                'paid' => round($rows->sum('paid_amount'), 4),
                'returns' => round($rows->sum(fn ($r) => (float) ($r->return_amount ?? 0)), 4),
                'outstanding' => round($rows->sum('balance'), 4),
                'records' => $rows->count(),
            ],
            'message' => null,
        ];
    }

    public function contactAging(int $business_id, string $contact_type, ?string $as_at, $location_id = null): array
    {
        $base = $this->contactOutstanding($business_id, $contact_type, $as_at, $location_id);
        $asAtDate = $as_at ? Carbon::parse($as_at) : now();
        $rows = collect($base['rows'] ?? [])->map(function ($r) use ($asAtDate) {
            $date = $r->transaction_date ? Carbon::parse($r->transaction_date) : $asAtDate;
            $days = max(0, $date->diffInDays($asAtDate, false));
            $amount = (float)$r->balance;
            $r->days = $days;
            $r->bucket_0_30 = $days <= 30 ? $amount : 0;
            $r->bucket_31_60 = $days >= 31 && $days <= 60 ? $amount : 0;
            $r->bucket_61_90 = $days >= 61 && $days <= 90 ? $amount : 0;
            $r->bucket_over_90 = $days > 90 ? $amount : 0;
            return $r;
        })->values();

        return [
            'rows' => $rows,
            'totals' => [
                'bucket_0_30' => round($rows->sum('bucket_0_30'), 4),
                'bucket_31_60' => round($rows->sum('bucket_31_60'), 4),
                'bucket_61_90' => round($rows->sum('bucket_61_90'), 4),
                'bucket_over_90' => round($rows->sum('bucket_over_90'), 4),
                'outstanding' => round($rows->sum('balance'), 4),
                'records' => $rows->count(),
            ],
            'message' => $base['message'] ?? null,
        ];
    }

    public function paymentMovement(int $business_id, string $contact_type, ?string $start, ?string $end, $location_id = null): array
    {
        if (!self::tableExists('transaction_payments') || !self::tableExists('transactions') || !self::tableExists('contacts')) {
            return ['rows' => collect(), 'totals' => ['amount' => 0, 'records' => 0], 'message' => 'Required payment/contact tables are not available.'];
        }

        $direction = $contact_type === 'supplier' ? 'purchase' : 'sell';
        $dateColumn = self::columnExists('transaction_payments', 'paid_on') ? 'transaction_payments.paid_on' : 'transaction_payments.created_at';
        $amountColumn = self::columnExists('transaction_payments', 'amount') ? 'transaction_payments.amount' : '0';

        $query = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.business_id', $business_id)
            ->where(function ($transactionTypeScope) use ($direction) {
                // Contact opening balances are stored as transactions.type =
                // opening_balance in the ERP and post to A/R or A/P. They are
                // part of the receivable/payable balance and must not disappear
                // merely because they are not normal sell/purchase invoices.
                $transactionTypeScope->where('transactions.type', $direction)
                    ->orWhere('transactions.type', 'opening_balance');
            })
            ->where('transactions.status', '!=', 'draft');

        $this->applyActiveTransactionFilters($query, 'transactions');
        $this->applyActivePaymentFilters($query, 'transaction_payments');

        if (self::columnExists('contacts', 'type')) {
            $query->where(function ($contactTypeScope) use ($contact_type) {
                $contactTypeScope->where('contacts.type', $contact_type)
                    ->orWhere('contacts.type', 'both');
            });
        }
        if ($start) {
            $query->where($dateColumn, '>=', self::dayStart($start));
        }
        if ($end) {
            $query->where($dateColumn, '<', self::dayAfter($end));
        }
        if ($location_id) {
            if (self::columnExists('transactions', 'location_id')) {
                $query->where('transactions.location_id', $location_id);
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $query->where('transactions.business_location_id', $location_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $rows = $query->select('transaction_payments.id', 'transaction_payments.method', 'transactions.invoice_no', 'transactions.ref_no', 'contacts.name as contact_name')
            ->selectRaw($dateColumn . ' as paid_on')
            ->selectRaw($amountColumn . ' as amount')
            ->orderBy($dateColumn)
            ->orderBy('transaction_payments.id')
            ->get()
            ->map(function ($r) {
                $r->amount = round((float) $r->amount, 4);
                return $r;
            });

        $byMethod = $rows->groupBy(fn ($r) => $r->method ?: 'Other')->map(function ($items, $method) {
            return (object) ['method' => $method, 'amount' => round($items->sum('amount'), 4), 'records' => $items->count()];
        })->values();

        return [
            'rows' => $rows,
            'by_method' => $byMethod,
            'totals' => ['amount' => round($rows->sum('amount'), 4), 'records' => $rows->count()],
            'message' => null,
        ];
    }

    public function receivablePayableSummary(int $business_id, string $contact_type, ?string $as_at, $location_id = null): array
    {
        $outstanding = $this->contactOutstanding($business_id, $contact_type, $as_at, $location_id);
        $aging = $this->contactAging($business_id, $contact_type, $as_at, $location_id);
        $rows = collect($outstanding['rows'] ?? [])->groupBy('contact_id')->map(function ($items) {
            $first = $items->first();
            return (object)[
                'contact_id' => $first->contact_id,
                'contact_name' => $first->contact_name,
                'mobile' => $first->mobile ?? '',
                'invoice_count' => $items->count(),
                'final_total' => round($items->sum('final_total'), 4),
                'paid_amount' => round($items->sum('paid_amount'), 4),
                'return_amount' => round($items->sum(fn ($r) => (float) ($r->return_amount ?? 0)), 4),
                'balance' => round($items->sum('balance'), 4),
            ];
        })->sortByDesc('balance')->values();

        return [
            'rows' => $rows,
            'aging_totals' => $aging['totals'] ?? [],
            'totals' => [
                'invoiced' => round($rows->sum('final_total'), 4),
                'paid' => round($rows->sum('paid_amount'), 4),
                'returns' => round($rows->sum('return_amount'), 4),
                'outstanding' => round($rows->sum('balance'), 4),
                'contacts' => $rows->count(),
            ],
            'message' => $outstanding['message'] ?? null,
        ];
    }

    public function contactStatement(int $business_id, string $contact_type, int $contact_id, ?string $start, ?string $end, $location_id = null): array
    {
        if (!self::tableExists('transactions') || !self::tableExists('contacts')) {
            return ['rows' => collect(), 'opening_balance' => 0, 'closing_balance' => 0, 'contact' => null, 'message' => 'Required transaction/contact tables are not available.'];
        }

        $direction = $contact_type === 'supplier' ? 'purchase' : 'sell';
        $openingEnd = $start ? Carbon::parse($start)->subDay()->format('Y-m-d') : null;
        $openingRows = $this->contactTransactionRows($business_id, $contact_type, $direction, null, $openingEnd, $location_id, $contact_id);
        $opening = round((float) $openingRows->sum('balance'), 4);
        $running = $opening;

        /*
         * Build a true period movement statement. The old implementation showed
         * invoice balances as at the end date, which meant payments made during
         * the period against an older invoice never reduced the running balance.
         * Here invoices increase the balance and payments/returns reduce it as
         * separate dated events.
         */
        $invoiceRows = $this->contactTransactionRows($business_id, $contact_type, $direction, $start, $end, $location_id, $contact_id)
            ->map(function ($r) {
                $invoiceValue = round((float) $r->final_total, 4);
                $r->paid_amount = 0.0;
                $r->return_amount = 0.0;
                $r->balance = $invoiceValue;
                $r->event_order = 10;
                return $r;
            });

        $paymentRows = $this->contactStatementPaymentRows($business_id, $contact_type, $direction, $contact_id, $start, $end, $location_id);
        $returnRows = $this->contactStatementReturnRows($business_id, $direction, $contact_id, $start, $end, $location_id);

        $rows = $invoiceRows
            ->concat($paymentRows)
            ->concat($returnRows)
            ->sort(function ($a, $b) {
                $dateCompare = strcmp((string) $a->transaction_date, (string) $b->transaction_date);
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }
                return ((int) ($a->event_order ?? 0)) <=> ((int) ($b->event_order ?? 0));
            })
            ->values()
            ->map(function ($r) use (&$running) {
                $running += (float) $r->balance;
                $r->running_balance = round($running, 4);
                return $r;
            });

        $contact = DB::table('contacts')->where('business_id', $business_id)->where('id', $contact_id)->first();

        return [
            'contact' => $contact,
            'rows' => $rows,
            'opening_balance' => $opening,
            'closing_balance' => round($running, 4),
            'totals' => [
                'invoiced' => round($rows->sum('final_total'), 4),
                'paid' => round($rows->sum('paid_amount'), 4),
                'returns' => round($rows->sum(fn ($r) => (float) ($r->return_amount ?? 0)), 4),
                'balance' => round($rows->sum('balance'), 4),
                'records' => $rows->count(),
            ],
            'message' => null,
        ];
    }

    public function contactsForDropdown(int $business_id, string $contact_type): Collection
    {
        if (!self::tableExists('contacts')) {
            return collect();
        }

        return DB::table('contacts')
            ->where('business_id', $business_id)
            ->when(self::columnExists('contacts', 'type'), function ($q) use ($contact_type) {
                return $q->where(function ($typeQuery) use ($contact_type) {
                    $typeQuery->where('type', $contact_type)
                        ->orWhere('type', 'both');
                });
            })
            ->when(self::columnExists('contacts', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function hasReceivablePayableTables(): bool
    {
        return self::tableExists('transactions') && self::tableExists('contacts');
    }

    private function emptyContactReport(string $message): array
    {
        return ['rows' => collect(), 'totals' => ['invoiced' => 0, 'paid' => 0, 'returns' => 0, 'outstanding' => 0, 'records' => 0], 'message' => $message];
    }

    private function contactBalanceRows(int $business_id, string $contact_type, ?string $as_at, $location_id, string $direction): Collection
    {
        return $this->contactTransactionRows($business_id, $contact_type, $direction, null, $as_at, $location_id, null);
    }

    private function contactTransactionRows(int $business_id, string $contact_type, string $direction, ?string $start, ?string $end, $location_id = null, ?int $contact_id = null): Collection
    {
        $paidExpression = $this->transactionPaidExpression($end);
        $returnExpression = $this->transactionReturnExpression($direction, $end, $location_id);
        $dateColumn = self::columnExists('transactions', 'transaction_date') ? 'transactions.transaction_date' : 'transactions.created_at';
        $locationColumn = self::columnExists('transactions', 'location_id') ? 'transactions.location_id' : (self::columnExists('transactions', 'business_location_id') ? 'transactions.business_location_id' : null);
        $refColumn = self::columnExists('transactions', 'invoice_no') ? 'transactions.invoice_no' : (self::columnExists('transactions', 'ref_no') ? 'transactions.ref_no' : 'transactions.id');

        $query = DB::table('transactions')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.business_id', $business_id)
            ->where(function ($transactionTypeScope) use ($direction) {
                // Contact opening balances are stored as transactions.type =
                // opening_balance in the ERP and post to A/R or A/P. They are
                // part of the receivable/payable balance and must not disappear
                // merely because they are not normal sell/purchase invoices.
                $transactionTypeScope->where('transactions.type', $direction)
                    ->orWhere('transactions.type', 'opening_balance');
            })
            ->where('transactions.status', '!=', 'draft');

        $this->applyActiveTransactionFilters($query, 'transactions');

        if (self::columnExists('contacts', 'type')) {
            $query->where(function ($contactTypeScope) use ($contact_type) {
                $contactTypeScope->where('contacts.type', $contact_type)
                    ->orWhere('contacts.type', 'both');
            });
        }
        if ($contact_id) {
            $query->where('transactions.contact_id', $contact_id);
        }
        if ($start) {
            $query->where($dateColumn, '>=', self::dayStart($start));
        }
        if ($end) {
            $query->where($dateColumn, '<', self::dayAfter($end));
        }
        if ($location_id) {
            if ($locationColumn) {
                $query->where($locationColumn, $location_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->select('transactions.id', 'transactions.contact_id', 'contacts.name as contact_name', 'contacts.mobile')
            ->selectRaw($dateColumn . ' as transaction_date')
            ->selectRaw($refColumn . ' as reference_no')
            ->selectRaw('COALESCE(transactions.final_total, 0) as final_total')
            ->selectRaw($paidExpression . ' as paid_amount')
            ->selectRaw($returnExpression . ' as return_amount')
            ->selectRaw('(COALESCE(transactions.final_total, 0) - ' . $paidExpression . ' - ' . $returnExpression . ') as balance')
            ->orderBy($dateColumn)
            ->orderBy('transactions.id')
            ->get()
            ->map(function ($r) {
                $r->final_total = round((float) $r->final_total, 4);
                $r->paid_amount = round((float) $r->paid_amount, 4);
                $r->return_amount = round((float) ($r->return_amount ?? 0), 4);
                $r->balance = round((float) $r->balance, 4);
                return $r;
            });
    }

    private function transactionPaidExpression(?string $asAt = null): string
    {
        if (self::columnExists('transactions', 'final_total')) {
            $paymentTableExists = self::tableExists('transaction_payments')
                && self::columnExists('transaction_payments', 'amount')
                && self::columnExists('transaction_payments', 'transaction_id');

            if ($paymentTableExists) {
                $conditions = ['tp.transaction_id = transactions.id'];

                if (self::columnExists('transaction_payments', 'is_return')) {
                    $conditions[] = '(tp.is_return IS NULL OR tp.is_return = 0)';
                }
                if (self::columnExists('transaction_payments', 'deleted_at')) {
                    $conditions[] = 'tp.deleted_at IS NULL';
                }
                if ($asAt) {
                    $dateColumn = self::columnExists('transaction_payments', 'paid_on') ? 'paid_on' : (self::columnExists('transaction_payments', 'created_at') ? 'created_at' : null);
                    if ($dateColumn) {
                        $bound = addslashes((string) self::dayAfter($asAt));
                        $conditions[] = 'tp.' . $dateColumn . " < '" . $bound . "'";
                    }
                }

                return '(SELECT COALESCE(SUM(tp.amount),0) FROM transaction_payments tp WHERE ' . implode(' AND ', $conditions) . ')';
            }
        }
        if (self::columnExists('transactions', 'total_paid')) {
            return 'COALESCE(transactions.total_paid, 0)';
        }
        if (self::columnExists('transactions', 'paid_amount')) {
            return 'COALESCE(transactions.paid_amount, 0)';
        }
        return '0';
    }

    public function accountsForDropdown(int $business_id): Collection
    {
        return DB::table('accounts')
            ->where('business_id', $business_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name', 'id');
    }




    public function cashFlowStatement(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $pl = $this->incomeStatement($business_id, $start, $end, $location_id);
        $cashOpening = $this->keywordBalance($business_id, ['cash', 'bank'], $start ? Carbon::parse($start)->subDay()->format('Y-m-d') : null, $location_id);
        $cashClosing = $this->keywordBalance($business_id, ['cash', 'bank'], $end, $location_id);
        $cashMovement = round($cashClosing - $cashOpening, 4);

        $classified = $this->cashFlowMovementsFromCashAccounts($business_id, $start, $end, $location_id);
        $operating = $classified['Operating Activities'];
        $investing = $classified['Investing Activities'];
        $financing = $classified['Financing Activities'];

        $classifiedMovement = round(
            (float) $operating->sum('amount')
            + (float) $investing->sum('amount')
            + (float) $financing->sum('amount'),
            4
        );

        // Some legacy entries do not retain pair/related-account references.
        // Put only that unmatched cash movement into Operating activities so the
        // statement always reconciles exactly to opening/closing cash.
        $reconciliation = round($cashMovement - $classifiedMovement, 4);
        if (abs($reconciliation) > 0.0001) {
            $operating->push((object) [
                'line' => 'Unclassified / legacy cash movement reconciliation',
                'amount' => $reconciliation,
            ]);
        }

        $netProfit = round((float) data_get($pl, 'totals.net_profit', 0), 4);
        $operatingTotal = round((float) $operating->sum('amount'), 4);

        // Present the indirect-method bridge while preserving any detailed
        // operating cash rows below it.
        $operatingBridge = collect([
            (object) ['line' => 'Net Profit / (Loss)', 'amount' => $netProfit],
            (object) ['line' => 'Operating working-capital / non-cash adjustments', 'amount' => round($operatingTotal - $netProfit, 4)],
        ]);

        return [
            'opening_cash' => round($cashOpening, 4),
            'closing_cash' => round($cashClosing, 4),
            'net_movement' => $cashMovement,
            'operating' => $operatingBridge,
            'operating_detail' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'totals' => [
                'operating' => $operatingTotal,
                'investing' => round($investing->sum('amount'), 4),
                'financing' => round($financing->sum('amount'), 4),
            ],
            'message' => 'Read-only cash flow statement reconciled to the actual Cash/Bank ledger movement. Existing Finance posting logic is not changed.',
        ];
    }

    public function positionReport(int $business_id, ?string $as_at, $location_id = null, array $keywords = [], string $title = ''): array
    {
        $rows = $this->accountsByPurpose($business_id, $keywords)
            ->map(function ($account) use ($business_id, $as_at, $location_id) {
                $balance = $this->accountBalance($business_id, (int)$account->id, $as_at, $location_id);
                return (object)[
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'account_number' => $account->account_number,
                    'ledger_balance' => round($balance, 4),
                    'available_balance' => round($balance, 4),
                    'difference' => 0,
                ];
            })
            ->filter(fn ($r) => abs((float)$r->ledger_balance) > 0.0001)
            ->values();

        return [
            'rows' => $rows,
            'totals' => [
                'ledger_balance' => round($rows->sum('ledger_balance'), 4),
                'available_balance' => round($rows->sum('available_balance'), 4),
                'difference' => round($rows->sum('difference'), 4),
                'records' => $rows->count(),
            ],
            'message' => 'Available balance currently follows ledger balance unless a separate bank-statement/reconciliation table exists.',
        ];
    }

    public function bankReconciliation(int $business_id, ?string $as_at, $location_id = null): array
    {
        $bankPosition = $this->positionReport($business_id, $as_at, $location_id, ['bank'], 'Bank Reconciliation - New');
        $bookBalance = (float)($bankPosition['totals']['ledger_balance'] ?? 0);

        // A bank reconciliation needs an independent bank-statement balance. If
        // the tenant has no statement table, pretending the book balance is the
        // statement balance and then applying all unreconciled payments creates a
        // false difference. In that schema, show a neutral/book-only position and
        // clearly state that external reconciliation data is unavailable.
        $hasBankStatementTable = self::tableExists('bank_statement_lines');
        $statementLocationColumn = $hasBankStatementTable
            ? (self::columnExists('bank_statement_lines', 'location_id')
                ? 'location_id'
                : (self::columnExists('bank_statement_lines', 'business_location_id') ? 'business_location_id' : null))
            : null;

        if (! $hasBankStatementTable || ($location_id && $statementLocationColumn === null)) {
            $message = ! $hasBankStatementTable
                ? 'Bank statement table not found. Book balance is shown for reference; reconciliation differences cannot be calculated until statement data is available.'
                : 'Bank statement data has no branch/location field. A branch reconciliation cannot safely use business-wide statement values, so only the branch book balance is shown.';

            return [
                'bank_accounts' => $bankPosition['rows'],
                'summary' => [
                    'book_balance' => round($bookBalance, 4),
                    'bank_statement_balance' => round($bookBalance, 4),
                    'outstanding_deposits' => 0.0,
                    'outstanding_cheques' => 0.0,
                    'adjusted_bank_balance' => round($bookBalance, 4),
                    'difference' => 0.0,
                ],
                'message' => $message,
            ];
        }

        $statementBalance = $this->bankStatementBalance($business_id, $as_at, $location_id);
        $outstandingDeposits = $this->unreconciledPayments($business_id, $as_at, $location_id, 'deposit');
        $outstandingCheques = $this->unreconciledPayments($business_id, $as_at, $location_id, 'cheque');
        $adjusted = round($statementBalance + $outstandingDeposits - $outstandingCheques, 4);

        return [
            'bank_accounts' => $bankPosition['rows'],
            'summary' => [
                'book_balance' => round($bookBalance, 4),
                'bank_statement_balance' => round($statementBalance, 4),
                'outstanding_deposits' => round($outstandingDeposits, 4),
                'outstanding_cheques' => round($outstandingCheques, 4),
                'adjusted_bank_balance' => $adjusted,
                'difference' => round($bookBalance - $adjusted, 4),
            ],
            'message' => null,
        ];
    }

    public function chequeRegister(int $business_id, ?string $start, ?string $end, $location_id = null, bool $postDatedOnly = false): array
    {
        if (!self::tableExists('transaction_payments')) {
            return ['rows' => collect(), 'totals' => ['amount' => 0, 'records' => 0], 'message' => 'transaction_payments table is not available.'];
        }

        // A cheque register is dated by the cheque itself when that field is
        // available. paid_on is the payment-entry date and is not the correct
        // basis for identifying post-dated cheques.
        $dateColumn = self::columnExists('transaction_payments', 'cheque_date')
            ? 'cheque_date'
            : (self::columnExists('transaction_payments', 'paid_on') ? 'paid_on' : 'created_at');
        $amountColumn = self::columnExists('transaction_payments', 'amount') ? 'amount' : DB::raw('0');
        $chequeNoColumn = self::columnExists('transaction_payments', 'cheque_number') ? 'cheque_number' : (self::columnExists('transaction_payments', 'cheque_no') ? 'cheque_no' : null);
        $query = DB::table('transaction_payments')
            ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where(function ($q) use ($business_id) {
                $q->where('transaction_payments.business_id', $business_id)->orWhere('transactions.business_id', $business_id);
            });

        $this->applyActivePaymentFilters($query, 'transaction_payments');
        $this->applyActiveTransactionFilters($query, 'transactions');

        if (self::columnExists('transaction_payments', 'method')) {
            $query->where(function ($q) {
                $q->where('transaction_payments.method', 'like', '%cheque%')
                  ->orWhere('transaction_payments.method', 'like', '%check%');
            });
        } elseif ($chequeNoColumn) {
            $query->whereNotNull('transaction_payments.' . $chequeNoColumn);
        }

        if ($start) {
            $query->whereDate('transaction_payments.' . $dateColumn, '>=', $start);
        }
        if ($end) {
            $query->whereDate('transaction_payments.' . $dateColumn, '<=', $end);
        }
        if ($location_id) {
            if (self::columnExists('transactions', 'location_id')) {
                $query->where('transactions.location_id', $location_id);
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $query->where('transactions.business_location_id', $location_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        if ($postDatedOnly) {
            $query->where(function ($postDated) use ($dateColumn) {
                if (self::columnExists('transaction_payments', 'post_dated_cheque')) {
                    $postDated->where('transaction_payments.post_dated_cheque', 1)
                        ->orWhereDate('transaction_payments.' . $dateColumn, '>', now()->format('Y-m-d'));
                } else {
                    $postDated->whereDate('transaction_payments.' . $dateColumn, '>', now()->format('Y-m-d'));
                }
            });
        }

        $select = ['transaction_payments.id', 'transaction_payments.method', 'transactions.invoice_no', 'transactions.ref_no', 'contacts.name as contact_name'];
        if ($chequeNoColumn) {
            $select[] = 'transaction_payments.' . $chequeNoColumn . ' as cheque_no';
        }
        foreach (['is_deposited', 'is_realized', 'post_dated_cheque'] as $statusColumn) {
            if (self::columnExists('transaction_payments', $statusColumn)) {
                $select[] = 'transaction_payments.' . $statusColumn;
            }
        }
        $rows = $query->select($select)
            ->selectRaw('transaction_payments.' . $dateColumn . ' as cheque_date')
            ->selectRaw((is_string($amountColumn) ? 'transaction_payments.' . $amountColumn : '0') . ' as amount')
            ->orderBy('transaction_payments.' . $dateColumn)
            ->orderBy('transaction_payments.id')
            ->get()
            ->map(function ($r) {
                $r->cheque_no = $r->cheque_no ?? '';
                $r->amount = round((float)$r->amount, 4);

                if ((int)($r->is_realized ?? 0) === 1) {
                    $r->status = 'Realized';
                } elseif ((int)($r->is_deposited ?? 0) === 1) {
                    $r->status = 'Deposited / Pending Realization';
                } elseif ((int)($r->post_dated_cheque ?? 0) === 1 || (! empty($r->cheque_date) && Carbon::parse($r->cheque_date)->isFuture())) {
                    $r->status = 'Post-Dated / Pending';
                } else {
                    $r->status = 'Pending / Not Deposited';
                }

                return $r;
            });

        return [
            'rows' => $rows,
            'totals' => ['amount' => round($rows->sum('amount'), 4), 'records' => $rows->count()],
            'message' => null,
        ];
    }

    public function cashMovementAnalysis(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $book = $this->bookByKeyword($business_id, $start, $end, $location_id, ['cash', 'bank']);
        $rows = collect($book['rows'] ?? [])->groupBy(function ($row) {
            return Carbon::parse($row->operation_date)->format('Y-m-d');
        })->map(function ($items, $date) {
            $inflow = round($items->sum('debit'), 4);
            $outflow = round($items->sum('credit'), 4);
            return (object)[
                'date' => $date,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net_movement' => round($inflow - $outflow, 4),
                'records' => $items->count(),
            ];
        })->sortBy('date')->values();

        return [
            'rows' => $rows,
            'totals' => [
                'inflow' => round($rows->sum('inflow'), 4),
                'outflow' => round($rows->sum('outflow'), 4),
                'net_movement' => round($rows->sum('net_movement'), 4),
                'records' => $rows->sum('records'),
            ],
        ];
    }

    private function cashFlowSectionByKeywords(int $business_id, ?string $start, ?string $end, $location_id = null, array $keywords = [], string $section = ''): Collection
    {
        $totals = $this->accountTotals($business_id, $start, $end, $location_id)
            ->filter(function ($r) use ($keywords, $section) {
                $category = $this->accountCategory($r);

                if ($section === 'Investing Activities' && $category !== 'Assets') {
                    return false;
                }
                if ($section === 'Financing Activities' && !in_array($category, ['Liabilities', 'Equity'], true)) {
                    return false;
                }

                $text = strtolower(($r->account_group_name ?? '') . ' ' . ($r->name ?? ''));
                foreach ($keywords as $keyword) {
                    if (str_contains($text, strtolower($keyword))) {
                        return true;
                    }
                }
                return false;
            })
            ->map(function ($r) {
                return (object)[
                    'line' => trim(($r->account_number ? $r->account_number . ' - ' : '') . $r->name),
                    // Cash-flow sign: increases in assets consume cash; increases
                    // in liabilities/equity provide cash. credit - debit gives the
                    // correct convention for both filtered sections.
                    'amount' => round((float)$r->credit - (float)$r->debit, 4),
                ];
            })
            ->filter(fn ($r) => abs((float)$r->amount) > 0.0001)
            ->values();

        return $totals;
    }

    private function keywordBalance(int $business_id, array $keywords, ?string $as_at, $location_id = null): float
    {
        $rows = $this->positionReport($business_id, $as_at, $location_id, $keywords);
        return round((float)($rows['totals']['ledger_balance'] ?? 0), 4);
    }

    /**
     * Find cash/bank/etc. accounts by the account itself OR its configured
     * account group/type. Finance uses groups such as "Bank Account" and
     * "Cash Account" as the authoritative classification, so depending only on
     * the account display name can silently omit valid accounts.
     */
    private function accountsMatchingKeywords(int $business_id, array $keywords): Collection
    {
        $query = DB::table('accounts')
            ->where('accounts.business_id', $business_id)
            ->whereNull('accounts.deleted_at');

        $hasGroups = self::tableExists('account_groups');
        $hasTypes = self::tableExists('account_types');

        if ($hasGroups) {
            $query->leftJoin('account_groups as search_account_groups', 'accounts.asset_type', '=', 'search_account_groups.id');
        }
        if ($hasTypes) {
            $query->leftJoin('account_types as search_account_types', 'accounts.account_type_id', '=', 'search_account_types.id');
        }

        $query->where(function ($q) use ($keywords, $hasGroups, $hasTypes) {
            foreach ($keywords as $keyword) {
                $pattern = '%' . $keyword . '%';
                $q->orWhere('accounts.name', 'like', $pattern)
                    ->orWhere('accounts.account_number', 'like', $pattern);
                if ($hasGroups) {
                    $q->orWhere('search_account_groups.name', 'like', $pattern);
                }
                if ($hasTypes) {
                    $q->orWhere('search_account_types.name', 'like', $pattern);
                }
            }
        });

        return $query->select('accounts.id', 'accounts.name', 'accounts.account_number')
            ->distinct()
            ->orderBy('accounts.name')
            ->get();
    }

    private function accountBalance(int $business_id, int $account_id, ?string $as_at, $location_id = null): float
    {
        $row = $this->transactionsQuery($business_id, null, $as_at, $location_id)
            ->where('account_transactions.account_id', $account_id)
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'debit' THEN account_transactions.amount ELSE 0 END) as debit")
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'credit' THEN account_transactions.amount ELSE 0 END) as credit")
            ->first();
        return round(((float)($row->debit ?? 0)) - ((float)($row->credit ?? 0)), 4);
    }

    private function bankStatementBalance(int $business_id, ?string $as_at, $location_id = null): float
    {
        if (!self::tableExists('bank_statement_lines')) {
            return $this->keywordBalance($business_id, ['bank'], $as_at, $location_id);
        }

        $query = DB::table('bank_statement_lines')->where('business_id', $business_id);
        if ($as_at && self::columnExists('bank_statement_lines', 'transaction_date')) {
            $query->where('transaction_date', '<', self::dayAfter($as_at));
        }
        if ($location_id) {
            if (self::columnExists('bank_statement_lines', 'location_id')) {
                $query->where('location_id', $location_id);
            } elseif (self::columnExists('bank_statement_lines', 'business_location_id')) {
                $query->where('business_location_id', $location_id);
            } else {
                return $this->keywordBalance($business_id, ['bank'], $as_at, $location_id);
            }
        }
        if (self::columnExists('bank_statement_lines', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $amountColumn = self::columnExists('bank_statement_lines', 'amount') ? 'amount' : null;
        return $amountColumn
            ? round((float)$query->sum($amountColumn), 4)
            : $this->keywordBalance($business_id, ['bank'], $as_at, $location_id);
    }

    private function unreconciledPayments(int $business_id, ?string $as_at, $location_id = null, string $type = 'deposit'): float
    {
        if (!self::tableExists('transaction_payments') || !self::columnExists('transaction_payments', 'amount')) {
            return 0.0;
        }

        $dateColumn = $type === 'cheque' && self::columnExists('transaction_payments', 'cheque_date')
            ? 'cheque_date'
            : (self::columnExists('transaction_payments', 'paid_on') ? 'paid_on' : 'created_at');

        $query = DB::table('transaction_payments')
            ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where(function ($q) use ($business_id) {
                $q->where('transaction_payments.business_id', $business_id)
                    ->orWhere('transactions.business_id', $business_id);
            });

        $this->applyActivePaymentFilters($query, 'transaction_payments');
        $this->applyActiveTransactionFilters($query, 'transactions');

        if ($as_at) {
            $query->where('transaction_payments.' . $dateColumn, '<', self::dayAfter($as_at));
        }
        if ($location_id) {
            if (self::columnExists('transactions', 'location_id')) {
                $query->where('transactions.location_id', $location_id);
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $query->where('transactions.business_location_id', $location_id);
            } else {
                // Bank reconciliation for a branch must not silently include all
                // branches when source payments have no branch field.
                $query->whereRaw('1 = 0');
            }
        }

        if ($type === 'cheque') {
            if (self::columnExists('transaction_payments', 'method')) {
                $query->where(function ($methodQuery) {
                    $methodQuery->where('transaction_payments.method', 'like', '%cheque%')
                        ->orWhere('transaction_payments.method', 'like', '%check%');
                });
            }

            if (self::columnExists('transaction_payments', 'is_realized')) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereNull('transaction_payments.is_realized')
                        ->orWhere('transaction_payments.is_realized', 0);
                });
            } elseif (self::columnExists('transaction_payments', 'is_reconciled')) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereNull('transaction_payments.is_reconciled')
                        ->orWhere('transaction_payments.is_reconciled', 0);
                });
            }
        } else {
            if (self::columnExists('transaction_payments', 'method')) {
                $query->where(function ($methodQuery) {
                    $methodQuery->whereNull('transaction_payments.method')
                        ->orWhere(function ($notCheque) {
                            $notCheque->where('transaction_payments.method', 'not like', '%cheque%')
                                ->where('transaction_payments.method', 'not like', '%check%');
                        });
                });
            }

            if (self::columnExists('transaction_payments', 'is_deposited')) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereNull('transaction_payments.is_deposited')
                        ->orWhere('transaction_payments.is_deposited', 0);
                });
            } elseif (self::columnExists('transaction_payments', 'is_reconciled')) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereNull('transaction_payments.is_reconciled')
                        ->orWhere('transaction_payments.is_reconciled', 0);
                });
            }
        }

        return round((float)$query->sum('transaction_payments.amount'), 4);
    }
    public function auditTrail(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $posted = $this->auditBaseRows($business_id, $start, $end, $location_id, false, null);
        $edited = $this->auditBaseRows($business_id, $start, $end, $location_id, false, 'updated');
        $deleted = $this->auditBaseRows($business_id, $start, $end, $location_id, true, null);

        $rows = $posted->concat($edited)->concat($deleted)
            ->sortByDesc(fn ($r) => (string) $r->date)
            ->take(1000)
            ->values()
            ->map(fn ($r) => [
                $r->date,
                $r->voucher,
                $r->account,
                $r->action,
                number_format((float) $r->debit, 4, '.', ''),
                number_format((float) $r->credit, 4, '.', ''),
                $r->deleted_by ?: ($r->updated_by ?: $r->created_by),
                $r->note,
            ]);

        return $this->auditResult($rows, ['Date', 'Voucher', 'Account', 'Action', 'Debit', 'Credit', 'User', 'Note']);
    }
    public function transactionHistory(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $rows = $this->auditBaseRows($business_id, $start, $end, $location_id, false, null)
            ->map(fn ($r) => [
                $r->date,
                $r->voucher,
                $r->account,
                ucfirst((string) $r->entry_type),
                number_format((float) $r->amount, 4, '.', ''),
                $r->created_by,
                $r->updated_by,
                $r->reference,
            ]);

        return $this->auditResult($rows, ['Date', 'Voucher', 'Account', 'Type', 'Amount', 'Created By', 'Updated By', 'Reference']);
    }
    public function deletedTransactions(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $rows = $this->auditBaseRows($business_id, $start, $end, $location_id, true, null)
            ->map(fn ($r) => [
                $r->date,
                $r->voucher,
                $r->account,
                $r->action . ' / ' . ucfirst((string) $r->entry_type),
                number_format((float) $r->amount, 4, '.', ''),
                $r->deleted_by,
                $r->note,
            ]);

        return $this->auditResult($rows, ['Deleted / Reversed Date', 'Voucher', 'Account', 'Type', 'Amount', 'Deleted / Reversed By', 'Note']);
    }
    public function editedTransactions(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $rows = $this->auditBaseRows($business_id, $start, $end, $location_id, false, 'updated')
            ->map(fn ($r) => [
                $r->date,
                $r->voucher,
                $r->account,
                ucfirst((string) $r->entry_type),
                number_format((float) $r->amount, 4, '.', ''),
                $r->updated_by,
                $r->note,
            ]);

        return $this->auditResult($rows, ['Updated Date', 'Voucher', 'Account', 'Type', 'Amount', 'Updated By', 'Note']);
    }
    public function voucherApprovalHistory(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        if (!self::tableExists('transactions')) {
            return $this->auditResult(collect(), ['Date', 'Voucher', 'Type', 'Status', 'Final Total', 'Created By', 'Updated By']);
        }

        $date = self::columnExists('transactions', 'transaction_date') ? 'transaction_date' : 'created_at';
        $q = DB::table('transactions')->where('business_id', $business_id);
        $this->applyActiveTransactionFilters($q, 'transactions');

        if ($start) { $q->where('transactions.' . $date, '>=', self::dayStart($start)); }
        if ($end) { $q->where('transactions.' . $date, '<', self::dayAfter($end)); }
        if ($location_id) {
            if (self::columnExists('transactions', 'location_id')) {
                $q->where('transactions.location_id', $location_id);
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $q->where('transactions.business_location_id', $location_id);
            } else {
                $q->whereRaw('1 = 0');
            }
        }

        $rows = $q->orderBy('transactions.' . $date, 'desc')->limit(500)->get()->map(function ($r) use ($date) {
            return [
                $r->{$date} ?? '',
                $r->invoice_no ?? $r->ref_no ?? $r->id ?? '',
                $r->type ?? $r->sub_type ?? '',
                $r->status ?? $r->payment_status ?? 'Posted',
                number_format((float)($r->final_total ?? 0), 4, '.', ''),
                $r->created_by ?? '',
                $r->updated_by ?? '',
            ];
        });
        return $this->auditResult($rows, ['Date', 'Voucher', 'Type', 'Status', 'Final Total', 'Created By', 'Updated By']);
    }

    public function financialLogViewer(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $posted = $this->auditBaseRows($business_id, $start, $end, $location_id, false, null);
        $edited = $this->auditBaseRows($business_id, $start, $end, $location_id, false, 'updated');
        $deleted = $this->auditBaseRows($business_id, $start, $end, $location_id, true, null);

        $rows = $posted->concat($edited)->concat($deleted)
            ->sortByDesc(fn ($r) => (string) $r->date)
            ->take(1000)
            ->values()
            ->map(fn ($r) => [
                $r->date,
                $r->voucher,
                $r->account,
                $r->action . ' / ' . ucfirst((string) $r->entry_type),
                number_format((float) $r->debit, 4, '.', ''),
                number_format((float) $r->credit, 4, '.', ''),
                $r->system_reference,
                $r->note,
            ]);

        return $this->auditResult($rows, ['Date', 'Voucher', 'Account', 'Entry Type', 'Debit', 'Credit', 'System Reference', 'Description']);
    }


    public function userFinancialActivity(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        if (!self::tableExists('account_transactions')) {
            return ['rows' => collect(), 'totals' => ['records' => 0, 'debit' => 0, 'credit' => 0]];
        }
        $q = $this->transactionsQuery($business_id, $start, $end, $location_id)
            ->leftJoin('users', 'account_transactions.created_by', '=', 'users.id')
            ->selectRaw('COALESCE(users.first_name, users.username, account_transactions.created_by, "System") as user_name')
            ->selectRaw('COUNT(account_transactions.id) as records')
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'debit' THEN account_transactions.amount ELSE 0 END) as debit")
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'credit' THEN account_transactions.amount ELSE 0 END) as credit")
            ->groupBy('user_name')
            ->orderByDesc('records')
            ->get();
        return ['rows' => $q, 'totals' => ['records' => (int)$q->sum('records'), 'debit' => round((float)$q->sum('debit'), 4), 'credit' => round((float)$q->sum('credit'), 4)]];
    }

    public function exceptionReport(int $business_id, ?string $as_at, $location_id = null): array
    {
        $exceptions = collect();
        if (self::tableExists('account_transactions')) {
            // Use the same inherited-business/date/location rules as Trial
            // Balance so this diagnostic cannot disagree merely because legacy
            // rows have account_transactions.business_id NULL/0.
            $q = $this->transactionsQuery($business_id, null, $as_at, $location_id);
            $totals = $q->selectRaw("SUM(CASE WHEN account_transactions.type = 'debit' THEN account_transactions.amount ELSE 0 END) as debit")
                ->selectRaw("SUM(CASE WHEN account_transactions.type = 'credit' THEN account_transactions.amount ELSE 0 END) as credit")
                ->first();
            $diff = round((float)($totals->debit ?? 0) - (float)($totals->credit ?? 0), 4);
            if (abs($diff) > 0.0001) {
                $exceptions->push((object)['type' => 'Trial Balance Difference', 'description' => 'Total debit and credit are not equal as at selected date.', 'amount' => $diff, 'severity' => 'High']);
            }

            // Rows with no account_id cannot inherit business scope from an
            // account, therefore only count rows explicitly belonging to this
            // business for the missing-mapping exception.
            $missingQuery = DB::table('account_transactions')
                ->where('account_transactions.business_id', $business_id)
                ->whereNull('account_transactions.account_id');
            $this->applyActiveAccountTransactionFilters($missingQuery, 'account_transactions');

            if ($as_at) {
                $missingQuery->where('account_transactions.operation_date', '<', self::dayAfter($as_at));
            }

            if ($location_id) {
                $sourceLocationColumn = null;
                if (self::tableExists('transactions')) {
                    $sourceLocationColumn = self::columnExists('transactions', 'location_id')
                        ? 'location_id'
                        : (self::columnExists('transactions', 'business_location_id') ? 'business_location_id' : null);
                }

                $hasDirect = $sourceLocationColumn
                    && self::columnExists('account_transactions', 'transaction_id');
                $hasPayment = $sourceLocationColumn
                    && self::tableExists('transaction_payments')
                    && self::columnExists('account_transactions', 'transaction_payment_id')
                    && self::columnExists('transaction_payments', 'transaction_id');

                if ($hasDirect) {
                    $missingQuery->leftJoin('transactions as exception_direct_transaction', 'exception_direct_transaction.id', '=', 'account_transactions.transaction_id');
                    $this->applyActiveTransactionFilters($missingQuery, 'exception_direct_transaction');
                }
                if ($hasPayment) {
                    $missingQuery->leftJoin('transaction_payments as exception_payment', 'exception_payment.id', '=', 'account_transactions.transaction_payment_id')
                        ->leftJoin('transactions as exception_payment_transaction', 'exception_payment_transaction.id', '=', 'exception_payment.transaction_id');
                    $this->applyActivePaymentFilters($missingQuery, 'exception_payment');
                    $this->applyActiveTransactionFilters($missingQuery, 'exception_payment_transaction');
                }

                if ($hasDirect || $hasPayment) {
                    $missingQuery->where(function ($locationScope) use ($location_id, $hasDirect, $hasPayment, $sourceLocationColumn) {
                        $locationScope->whereRaw('1 = 0');
                        if ($hasDirect) {
                            $locationScope->orWhere('exception_direct_transaction.' . $sourceLocationColumn, $location_id);
                        }
                        if ($hasPayment) {
                            $locationScope->orWhere(function ($paymentScope) use ($location_id, $hasDirect, $sourceLocationColumn) {
                                if ($hasDirect) {
                                    $paymentScope->whereNull('exception_direct_transaction.' . $sourceLocationColumn);
                                }
                                $paymentScope->where('exception_payment_transaction.' . $sourceLocationColumn, $location_id);
                            });
                        }
                    });
                } else {
                    // There is no account_id to fall back to, so a branch cannot
                    // be resolved safely for these exceptional rows.
                    $missingQuery->whereRaw('1 = 0');
                }
            }
            $missing = $missingQuery->count();
            if ($missing > 0) {
                $exceptions->push((object)['type' => 'Missing Account Mapping', 'description' => $missing . ' accounting entries do not have account_id.', 'amount' => $missing, 'severity' => 'High']);
            }
        }
        return ['rows' => $exceptions, 'totals' => ['records' => $exceptions->count(), 'amount' => round((float)$exceptions->sum('amount'), 4)]];
    }



    public function fixedAssetDashboard(int $business_id, ?string $as_at, $location_id = null): array
    {
        $register = $this->fixedAssetRegister($business_id, $as_at, $location_id);
        $categories = $this->assetCategorySummary($business_id, $as_at, $location_id);

        return [
            'cards' => [
                'assets' => $register['totals']['records'] ?? 0,
                'cost' => $register['totals']['cost'] ?? 0,
                'depreciation' => $register['totals']['depreciation'] ?? 0,
                'book_value' => $register['totals']['book_value'] ?? 0,
                'categories' => $categories['totals']['records'] ?? 0,
            ],
            'categories' => $categories['rows'],
            'recent_assets' => $register['rows']->take(25),
        ];
    }

    public function fixedAssetRegister(int $business_id, ?string $as_at, $location_id = null): array
    {
        $rows = $this->assetRows($business_id, $as_at, $location_id)->map(function ($r) {
            $r->cost = round((float)($r->cost ?? 0), 4);
            $r->depreciation = round((float)($r->depreciation ?? 0), 4);
            $r->book_value = round($r->cost - $r->depreciation, 4);
            return $r;
        });

        return [
            'rows' => $rows,
            'totals' => [
                'records' => $rows->count(),
                'cost' => round($rows->sum('cost'), 4),
                'depreciation' => round($rows->sum('depreciation'), 4),
                'book_value' => round($rows->sum('book_value'), 4),
            ],
        ];
    }

    public function depreciationRegister(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $assets = $this->assetRows($business_id, $end, $location_id);
        $rows = $assets->map(function ($r) {
            $cost = (float)($r->cost ?? 0);
            $dep = (float)($r->depreciation ?? 0);
            return (object) [
                'asset_code' => $r->asset_code,
                'asset_name' => $r->asset_name,
                'category' => $r->category,
                'location_name' => $r->location_name,
                'opening_value' => round($cost, 4),
                'depreciation' => round($dep, 4),
                'closing_value' => round($cost - $dep, 4),
                'status' => $r->status,
            ];
        });

        return [
            'rows' => $rows,
            'totals' => [
                'opening_value' => round($rows->sum('opening_value'), 4),
                'depreciation' => round($rows->sum('depreciation'), 4),
                'closing_value' => round($rows->sum('closing_value'), 4),
                'records' => $rows->count(),
            ],
        ];
    }

    public function assetMovementRegister(int $business_id, ?string $start, ?string $end, $location_id = null, ?string $mode = null): array
    {
        $table = $this->assetMovementTable();
        if (!$table) {
            return ['rows' => collect(), 'totals' => ['records' => 0, 'amount' => 0]];
        }

        $q = DB::table($table)->where($table . '.business_id', $business_id);
        if (self::columnExists($table, 'deleted_at')) { $q->whereNull($table . '.deleted_at'); }
        $dateCol = self::columnExists($table, 'transaction_datetime')
            ? 'transaction_datetime'
            : (self::columnExists($table, 'transaction_date')
                ? 'transaction_date'
                : (self::columnExists($table, 'date') ? 'date' : 'created_at'));
        if ($start && self::columnExists($table, $dateCol)) { $q->whereDate($dateCol, '>=', $start); }
        if ($end && self::columnExists($table, $dateCol)) { $q->whereDate($dateCol, '<=', $end); }
        if ($mode) {
            $q->where(function ($w) use ($table, $mode) {
                foreach (['type','movement_type','status','transaction_type'] as $col) {
                    if (self::columnExists($table, $col)) { $w->orWhere($col, 'like', '%' . $mode . '%'); }
                }
            });
        }

        // The standard asset_transactions table stores quantity, not a money
        // amount. When available, join the asset master so this report can show
        // the moved asset's name/code and a monetary movement value based on the
        // recorded unit price instead of reporting every movement as 0.0000.
        $joinedAssetMaster = $table === 'asset_transactions'
            && self::tableExists('assets')
            && self::columnExists($table, 'asset_id');
        if ($joinedAssetMaster) {
            $q->leftJoin('assets as movement_asset', $table . '.asset_id', '=', 'movement_asset.id');
            $q->select($table . '.*')
                ->addSelect('movement_asset.asset_code as joined_asset_code')
                ->addSelect('movement_asset.name as joined_asset_name')
                ->addSelect('movement_asset.unit_price as joined_unit_price');
        } else {
            $q->select($table . '.*');
        }

        if ($location_id) {
            if (self::columnExists($table, 'location_id')) {
                $q->where($table . '.location_id', $location_id);
            } elseif (self::columnExists($table, 'from_location_id') || self::columnExists($table, 'to_location_id')) {
                $q->where(function ($movementLocation) use ($table, $location_id) {
                    $hasFrom = self::columnExists($table, 'from_location_id');
                    $hasTo = self::columnExists($table, 'to_location_id');
                    if ($hasFrom) {
                        $movementLocation->where($table . '.from_location_id', $location_id);
                    }
                    if ($hasTo) {
                        $hasFrom
                            ? $movementLocation->orWhere($table . '.to_location_id', $location_id)
                            : $movementLocation->where($table . '.to_location_id', $location_id);
                    }
                });
            } elseif ($joinedAssetMaster && self::columnExists('assets', 'location_id')) {
                $q->where('movement_asset.location_id', $location_id);
            } else {
                // No trustworthy location source for this movement table.
                $q->whereRaw('1 = 0');
            }
        }

        $rows = $q->orderBy($table . '.' . $dateCol, 'desc')->limit(500)->get()->map(function ($r) use ($dateCol, $joinedAssetMaster) {
            $quantity = (float)($r->quantity ?? 0);
            $unitPrice = (float)($r->joined_unit_price ?? $r->unit_price ?? 0);
            $amount = (float)($r->amount ?? $r->value ?? $r->cost ?? 0);
            if ($joinedAssetMaster && abs($amount) < 0.0001 && abs($quantity) > 0.0001) {
                $amount = $quantity * $unitPrice;
            }

            return (object) [
                'date' => $r->{$dateCol} ?? '',
                'asset_code' => $r->asset_code ?? $r->joined_asset_code ?? $r->code ?? $r->asset_id ?? '',
                'asset_name' => $r->asset_name ?? $r->joined_asset_name ?? $r->name ?? '',
                'movement_type' => $r->movement_type ?? $r->transaction_type ?? $r->type ?? $r->status ?? '',
                'from_location' => $r->from_location_id ?? $r->from_location ?? '',
                'to_location' => $r->to_location_id ?? $r->to_location ?? '',
                'amount' => round($amount, 4),
                'remarks' => $r->remarks ?? $r->note ?? $r->reason ?? $r->description ?? '',
            ];
        });

        return ['rows' => $rows, 'totals' => ['records' => $rows->count(), 'amount' => round($rows->sum('amount'), 4)]];
    }

    public function assetCategorySummary(int $business_id, ?string $as_at, $location_id = null): array
    {
        $rows = $this->assetRows($business_id, $as_at, $location_id)
            ->groupBy(fn ($r) => $r->category ?: 'Uncategorized')
            ->map(function ($items, $category) {
                return (object) [
                    'category' => $category,
                    'records' => $items->count(),
                    'cost' => round($items->sum('cost'), 4),
                    'depreciation' => round($items->sum('depreciation'), 4),
                    'book_value' => round($items->sum('book_value'), 4),
                ];
            })->values();

        return ['rows' => $rows, 'totals' => ['records' => $rows->count(), 'cost' => round($rows->sum('cost'), 4), 'depreciation' => round($rows->sum('depreciation'), 4), 'book_value' => round($rows->sum('book_value'), 4)]];
    }

    public function assetValuationReport(int $business_id, ?string $as_at, $location_id = null): array
    {
        return $this->fixedAssetRegister($business_id, $as_at, $location_id);
    }

    private function assetRows(int $business_id, ?string $as_at, $location_id = null): Collection
    {
        $table = $this->assetTable();
        if (!$table) {
            return collect();
        }

        $q = DB::table($table)->where('business_id', $business_id);
        if (self::columnExists($table, 'deleted_at')) { $q->whereNull('deleted_at'); }
        $assetDateColumn = self::columnExists($table, 'purchase_date')
            ? 'purchase_date'
            : (self::columnExists($table, 'date_of_operation')
                ? 'date_of_operation'
                : (self::columnExists($table, 'created_at') ? 'created_at' : null));
        if ($as_at && $assetDateColumn) { $q->whereDate($assetDateColumn, '<=', $as_at); }
        if ($location_id) {
            if (self::columnExists($table, 'location_id')) {
                $q->where('location_id', $location_id);
            } elseif (self::columnExists($table, 'asset_location')) {
                // Finance's fixed_assets table stores a textual asset_location
                // instead of location_id. Match both the selected id (some older
                // rows stored it as text) and the official Business Location name.
                $locationName = self::tableExists('business_locations')
                    ? DB::table('business_locations')
                        ->where('business_id', $business_id)
                        ->where('id', $location_id)
                        ->value('name')
                    : null;

                $q->where(function ($locationQuery) use ($location_id, $locationName) {
                    $locationQuery->where('asset_location', (string)$location_id);
                    if (! empty($locationName)) {
                        $locationQuery->orWhere('asset_location', $locationName);
                    }
                });
            } else {
                // A requested branch must not receive every asset in the
                // business when this asset table has no location dimension.
                $q->whereRaw('1 = 0');
            }
        }

        $rows = $q->limit(1000)->get()->map(function ($r) use ($table) {
            $cost = (float)($r->purchase_price ?? $r->purchase_cost ?? $r->cost ?? $r->amount ?? $r->value ?? 0);
            if (abs($cost) < 0.0001 && isset($r->unit_price)) {
                $cost = (float)$r->unit_price * (float)($r->quantity ?? 1);
            }
            $dep = (float)($r->accumulated_depreciation ?? $r->depreciation ?? 0);
            return (object) [
                'asset_code' => $r->asset_code ?? $r->code ?? $r->sku ?? $r->id,
                'asset_name' => $r->asset_name ?? $r->name ?? $r->description ?? '',
                'category' => $r->category ?? $r->asset_category ?? $r->category_name ?? '',
                'location_name' => $r->location_name ?? $r->business_location_name ?? $r->asset_location ?? $r->location_id ?? '',
                'purchase_date' => $r->purchase_date ?? $r->date_of_operation ?? $r->created_at ?? '',
                'cost' => round($cost, 4),
                'depreciation' => round($dep, 4),
                'book_value' => round($cost - $dep, 4),
                'status' => $r->status ?? 'Active',
            ];
        });
        return $rows;
    }

    private function assetTable(): ?string
    {
        foreach (['fixed_assets', 'assets', 'asset_registers', 'finance_assets'] as $table) {
            if (self::tableExists($table)) { return $table; }
        }
        return null;
    }

    private function assetMovementTable(): ?string
    {
        foreach (['asset_movements', 'fixed_asset_movements', 'asset_transfers', 'asset_transactions'] as $table) {
            if (self::tableExists($table)) { return $table; }
        }
        return null;
    }
    private function auditBaseRows(int $business_id, ?string $start, ?string $end, $location_id = null, bool $onlyDeleted = false, ?string $mode = null): Collection
    {
        if (!self::tableExists('account_transactions')) {
            return collect();
        }

        $hasAtLocation = self::columnExists('account_transactions', 'location_id');
        $transactionLocationColumn = null;
        if (self::tableExists('transactions')) {
            if (self::columnExists('transactions', 'location_id')) {
                $transactionLocationColumn = 'location_id';
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $transactionLocationColumn = 'business_location_id';
            }
        }

        $hasDirectTransaction = self::tableExists('transactions')
            && self::columnExists('account_transactions', 'transaction_id');
        $hasPaymentTransaction = self::tableExists('transaction_payments')
            && self::tableExists('transactions')
            && self::columnExists('account_transactions', 'transaction_payment_id')
            && self::columnExists('transaction_payments', 'transaction_id');
        $hasAccountLocation = self::columnExists('accounts', 'location_id');

        $q = DB::table('account_transactions')
            ->leftJoin('accounts as audit_account', 'account_transactions.account_id', '=', 'audit_account.id');

        if ($hasDirectTransaction) {
            $q->leftJoin('transactions as audit_direct_transaction', 'audit_direct_transaction.id', '=', 'account_transactions.transaction_id');
        }
        if ($hasPaymentTransaction) {
            $q->leftJoin('transaction_payments as audit_payment', 'audit_payment.id', '=', 'account_transactions.transaction_payment_id')
                ->leftJoin('transactions as audit_payment_transaction', 'audit_payment_transaction.id', '=', 'audit_payment.transaction_id');
        }

        // Legacy accounting rows can have business_id NULL/0. Admit them only
        // when the linked Account belongs to this business, so the audit scope is
        // complete without leaking rows from another tenant/business.
        $q->where(function ($transactionBusiness) use ($business_id) {
            $transactionBusiness->where('account_transactions.business_id', $business_id)
                ->orWhere(function ($inherited) use ($business_id) {
                    $inherited->where(function ($missing) {
                        $missing->whereNull('account_transactions.business_id')
                            ->orWhere('account_transactions.business_id', 0);
                    })->where('audit_account.business_id', $business_id);
                });
        });

        $hasDeletedAt = self::columnExists('account_transactions', 'deleted_at');
        $hasNewDeletedAt = self::columnExists('account_transactions', 'new_deleted_at');
        $hasReversed = self::columnExists('account_transactions', 'reversed');
        $hasJournalDeleted = self::columnExists('account_transactions', 'journal_deleted');

        if ($onlyDeleted) {
            // This application uses both the Laravel soft-delete field and a
            // second new_deleted_at flag; edited entries can also be superseded
            // by reversed/journal_deleted. All are non-active accounting rows and
            // belong in the Deleted/Reversed audit report.
            $q->where(function ($deleted) use ($hasDeletedAt, $hasNewDeletedAt, $hasReversed, $hasJournalDeleted) {
                $added = false;
                if ($hasDeletedAt) {
                    $deleted->whereNotNull('account_transactions.deleted_at');
                    $added = true;
                }
                if ($hasNewDeletedAt) {
                    ($added ? $deleted->orWhereNotNull('account_transactions.new_deleted_at') : $deleted->whereNotNull('account_transactions.new_deleted_at'));
                    $added = true;
                }
                if ($hasReversed) {
                    ($added ? $deleted->orWhere('account_transactions.reversed', 1) : $deleted->where('account_transactions.reversed', 1));
                    $added = true;
                }
                if ($hasJournalDeleted) {
                    ($added ? $deleted->orWhere('account_transactions.journal_deleted', 1) : $deleted->where('account_transactions.journal_deleted', 1));
                    $added = true;
                }
                if (!$added) {
                    $deleted->whereRaw('1 = 0');
                }
            });
        } else {
            $this->applyActiveAccountTransactionFilters($q, 'account_transactions');

            // If the accounting row points to a deleted/superseded source
            // transaction/payment, it is not an active financial posting.
            if ($hasDirectTransaction) {
                $this->applyActiveTransactionFilters($q, 'audit_direct_transaction');
            }
            if ($hasPaymentTransaction) {
                $this->applyActivePaymentFilters($q, 'audit_payment');
                $this->applyActiveTransactionFilters($q, 'audit_payment_transaction');
            }
        }

        if ($mode === 'updated') {
            if (!self::columnExists('account_transactions', 'updated_at')) {
                return collect();
            }
            $q->whereNotNull('account_transactions.updated_at');
            if (self::columnExists('account_transactions', 'updated_by')) {
                $q->where(function ($edited) {
                    $edited->whereNotNull('account_transactions.updated_by');
                    if (self::columnExists('account_transactions', 'created_at')) {
                        $edited->orWhereColumn('account_transactions.updated_at', '>', 'account_transactions.created_at');
                    }
                });
            } elseif (self::columnExists('account_transactions', 'created_at')) {
                $q->whereColumn('account_transactions.updated_at', '>', 'account_transactions.created_at');
            }
        }

        if ($onlyDeleted) {
            $dateExpressionParts = [];
            if ($hasNewDeletedAt) { $dateExpressionParts[] = 'account_transactions.new_deleted_at'; }
            if ($hasDeletedAt) { $dateExpressionParts[] = 'account_transactions.deleted_at'; }
            if (self::columnExists('account_transactions', 'updated_at')) { $dateExpressionParts[] = 'account_transactions.updated_at'; }
            $dateExpressionParts[] = 'account_transactions.operation_date';
            $dateExpression = 'COALESCE(' . implode(', ', $dateExpressionParts) . ')';
        } elseif ($mode === 'updated' && self::columnExists('account_transactions', 'updated_at')) {
            $dateExpression = 'account_transactions.updated_at';
        } else {
            $dateExpression = 'account_transactions.operation_date';
        }

        if ($start) {
            $q->whereRaw($dateExpression . ' >= ?', [self::dayStart($start)]);
        }
        if ($end) {
            $q->whereRaw($dateExpression . ' < ?', [self::dayAfter($end)]);
        }

        if ($location_id) {
            $hasDirectLocation = $hasDirectTransaction && $transactionLocationColumn !== null;
            $hasPaymentLocation = $hasPaymentTransaction && $transactionLocationColumn !== null;

            if (!($hasAtLocation || $hasDirectLocation || $hasPaymentLocation || $hasAccountLocation)) {
                // Never turn a requested branch audit into a hidden consolidated
                // audit just because this tenant schema lacks a location source.
                $q->whereRaw('1 = 0');
            } else {
                $q->where(function ($location) use (
                    $location_id,
                    $hasAtLocation,
                    $hasDirectLocation,
                    $hasPaymentLocation,
                    $hasAccountLocation,
                    $transactionLocationColumn
                ) {
                    $location->whereRaw('1 = 0');

                    if ($hasAtLocation) {
                        $location->orWhere('account_transactions.location_id', $location_id);
                    }

                    if ($hasDirectLocation) {
                        $location->orWhere(function ($direct) use ($location_id, $hasAtLocation, $transactionLocationColumn) {
                            if ($hasAtLocation) {
                                $direct->where(function ($missingAt) {
                                    $missingAt->whereNull('account_transactions.location_id')
                                        ->orWhere('account_transactions.location_id', '');
                                });
                            }
                            $direct->where('audit_direct_transaction.' . $transactionLocationColumn, $location_id);
                        });
                    }

                    if ($hasPaymentLocation) {
                        $location->orWhere(function ($payment) use ($location_id, $hasAtLocation, $hasDirectLocation, $transactionLocationColumn) {
                            if ($hasAtLocation) {
                                $payment->where(function ($missingAt) {
                                    $missingAt->whereNull('account_transactions.location_id')
                                        ->orWhere('account_transactions.location_id', '');
                                });
                            }
                            if ($hasDirectLocation) {
                                $payment->whereNull('audit_direct_transaction.' . $transactionLocationColumn);
                            }
                            $payment->where('audit_payment_transaction.' . $transactionLocationColumn, $location_id);
                        });
                    }

                    if ($hasAccountLocation) {
                        $location->orWhere(function ($fallback) use (
                            $location_id,
                            $hasAtLocation,
                            $hasDirectLocation,
                            $hasPaymentLocation,
                            $transactionLocationColumn
                        ) {
                            if ($hasAtLocation) {
                                $fallback->where(function ($missingAt) {
                                    $missingAt->whereNull('account_transactions.location_id')
                                        ->orWhere('account_transactions.location_id', '');
                                });
                            }
                            if ($hasDirectLocation) {
                                $fallback->whereNull('audit_direct_transaction.' . $transactionLocationColumn);
                            }
                            if ($hasPaymentLocation) {
                                $fallback->whereNull('audit_payment_transaction.' . $transactionLocationColumn);
                            }
                            $fallback->where('audit_account.location_id', (string) $location_id);
                        });
                    }
                });
            }
        }

        $select = [
            'account_transactions.*',
            'audit_account.name as account_name',
            'audit_account.account_number as account_number',
        ];
        if ($hasDirectTransaction) {
            if (self::columnExists('transactions', 'invoice_no')) { $select[] = 'audit_direct_transaction.invoice_no as source_invoice_no'; }
            if (self::columnExists('transactions', 'ref_no')) { $select[] = 'audit_direct_transaction.ref_no as source_ref_no'; }
        }
        if ($hasPaymentTransaction) {
            if (self::columnExists('transactions', 'invoice_no')) { $select[] = 'audit_payment_transaction.invoice_no as payment_invoice_no'; }
            if (self::columnExists('transactions', 'ref_no')) { $select[] = 'audit_payment_transaction.ref_no as payment_ref_no'; }
        }

        return $q->select($select)
            ->selectRaw($dateExpression . ' as audit_event_date')
            ->orderByRaw($dateExpression . ' DESC')
            ->orderByDesc('account_transactions.id')
            ->limit(1000)
            ->get()
            ->map(function ($r) use ($onlyDeleted, $mode) {
                $amount = round((float) ($r->amount ?? 0), 4);
                $type = (string) ($r->type ?? '');
                $debit = $type === 'debit' ? $amount : 0.0;
                $credit = $type === 'credit' ? $amount : 0.0;

                if ($onlyDeleted) {
                    if ((int) ($r->reversed ?? 0) === 1) {
                        $action = 'Reversed / Superseded';
                    } elseif ((int) ($r->journal_deleted ?? 0) === 1) {
                        $action = 'Journal Deleted';
                    } else {
                        $action = 'Deleted';
                    }
                } elseif ($mode === 'updated') {
                    $action = 'Edited / Updated';
                } else {
                    $action = 'Posted';
                }

                $voucher = $r->source_invoice_no
                    ?? $r->source_ref_no
                    ?? $r->payment_invoice_no
                    ?? $r->payment_ref_no
                    ?? $r->reff_no
                    ?? $r->cheque_ref_no
                    ?? $r->transaction_id
                    ?? $r->id
                    ?? '';

                $reference = $r->reff_no
                    ?? $r->cheque_ref_no
                    ?? $r->source_collection_form_no
                    ?? $r->source_pump_operator_payment_id
                    ?? $r->transaction_payment_id
                    ?? $r->transaction_id
                    ?? '';

                $deletedBy = $r->new_deleted_by ?? $r->deleted_by ?? $r->updated_by ?? '';

                return (object) [
                    'date' => $r->audit_event_date ?? $r->operation_date ?? '',
                    'voucher' => $voucher,
                    'account' => $r->account_name ?? '',
                    'account_number' => $r->account_number ?? '',
                    'entry_type' => $type,
                    'action' => $action,
                    'amount' => $amount,
                    'debit' => $debit,
                    'credit' => $credit,
                    'created_by' => $r->created_by ?? '',
                    'updated_by' => $r->updated_by ?? '',
                    'deleted_by' => $deletedBy,
                    'reference' => $reference,
                    'system_reference' => $r->transaction_id ?? $r->transaction_payment_id ?? $r->id ?? '',
                    'note' => $r->note ?? '',
                ];
            });
    }


    private function auditResult(Collection $rows, array $headers): array
    {
        return ['headers' => $headers, 'rows' => $rows, 'totals' => ['records' => $rows->count()]];
    }

    private function budgetAmount(int $business_id, int $account_id, ?string $start, ?string $end, $location_id = null): float
    {
        $table = self::tableExists('account_budgets') ? 'account_budgets' : (self::tableExists('budgets') ? 'budgets' : null);
        if (!$table) {
            return 0.0;
        }

        $query = DB::table($table)->where('business_id', $business_id);
        if (self::columnExists($table, 'account_id')) {
            $query->where('account_id', $account_id);
        }
        if ($location_id) {
            if (self::columnExists($table, 'location_id')) {
                $query->where($table . '.location_id', $location_id);
            } else {
                // A branch budget-vs-actual report must not silently compare a
                // branch's actual results against a business-wide budget when
                // the budget table itself has no branch/location dimension.
                return 0.0;
            }
        }

        // Budget rows should be included when their period OVERLAPS the report
        // period. Requiring budget.start >= report.start AND budget.end <=
        // report.end excluded monthly/annual budgets that only partly overlap.
        if ($end && self::columnExists($table, 'start_date')) {
            $query->whereDate($table . '.start_date', '<=', $end);
        }
        if ($start && self::columnExists($table, 'end_date')) {
            $query->whereDate($table . '.end_date', '>=', $start);
        }
        $amount_column = self::columnExists($table, 'amount') ? 'amount' : (self::columnExists($table, 'budget_amount') ? 'budget_amount' : null);
        return $amount_column ? round((float)$query->sum($amount_column), 4) : 0.0;
    }

    private function ratio(float $numerator, float $denominator): float
    {
        return abs($denominator) > 0.0001 ? round($numerator / $denominator, 4) : 0.0;
    }



    public function forecast(int $business_id, ?string $start, ?string $end, $location_id = null, string $type = 'profit', int $months = 6): array
    {
        $months = max(1, min($months, 24));
        $startDate = $start ? Carbon::parse($start) : now()->subMonths(6)->startOfMonth();
        $endDate = $end ? Carbon::parse($end) : now()->endOfMonth();
        $historyStart = $startDate->copy()->subMonths(max(6, $months))->startOfMonth()->format('Y-m-d');
        $historyEnd = $endDate->copy()->endOfMonth()->format('Y-m-d');

        $pl = $this->incomeStatement($business_id, $historyStart, $historyEnd, $location_id);
        $income = (float) data_get($pl, 'totals.income', 0);
        $revenue = (float) data_get($pl, 'totals.revenue', $income);
        $expenses = (float) data_get($pl, 'totals.expenses', 0);
        $profit = (float) data_get($pl, 'totals.net_profit', $income - $expenses);
        $historyMonths = max(
            1,
            Carbon::parse($historyStart)->startOfMonth()->diffInMonths(Carbon::parse($historyEnd)->startOfMonth()) + 1
        );

        $cashFlowHistory = 0.0;
        if ($type === 'cash_flow') {
            $historyOpening = $this->keywordBalance(
                $business_id,
                ['cash', 'bank'],
                Carbon::parse($historyStart)->subDay()->format('Y-m-d'),
                $location_id
            );
            $historyClosing = $this->keywordBalance($business_id, ['cash', 'bank'], $historyEnd, $location_id);
            $cashFlowHistory = round($historyClosing - $historyOpening, 4);
        }

        $base = match ($type) {
            'cash_flow' => $cashFlowHistory,
            'revenue' => $revenue,
            'expense' => $expenses,
            default => $profit,
        };
        $monthlyAverage = round($base / $historyMonths, 4);

        $rows = collect();
        for ($i = 1; $i <= $months; $i++) {
            $month = $endDate->copy()->addMonthsNoOverflow($i)->format('Y-m');
            $growth = 1 + (0.01 * $i);
            $rows->push((object) [
                'month' => $month,
                'projected_amount' => round($monthlyAverage * $growth, 4),
                'basis' => 'Historical monthly average with conservative 1% monthly trend',
            ]);
        }

        return [
            'type' => $type,
            'history_start' => $historyStart,
            'history_end' => $historyEnd,
            'months' => $months,
            'monthly_average' => $monthlyAverage,
            'rows' => $rows,
            'totals' => [
                'projected_total' => round($rows->sum('projected_amount'), 4),
                'record_count' => $rows->count(),
            ],
        ];
    }

    private function accountTotals(int $business_id, ?string $start, ?string $end, $location_id): Collection
    {
        /*
         * Aggregate posting movement first using the SAME active-entry and
         * location resolver as ledgers/books. This ERP's account_transactions
         * table commonly has no location_id, so branch attribution must come
         * from the linked transaction or linked payment's transaction.
         */
        $movement = $this->transactionsQuery($business_id, $start, $end, $location_id)
            ->select('account_transactions.account_id')
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'debit' THEN account_transactions.amount ELSE 0 END) as debit")
            ->selectRaw("SUM(CASE WHEN account_transactions.type = 'credit' THEN account_transactions.amount ELSE 0 END) as credit")
            ->groupBy('account_transactions.account_id');

        $query = DB::table('accounts')
            ->leftJoinSub($movement, 'fr_movement', function ($join) {
                $join->on('accounts.id', '=', 'fr_movement.account_id');
            })
            ->where('accounts.business_id', $business_id);

        if (self::columnExists('accounts', 'deleted_at')) {
            $query->whereNull('accounts.deleted_at');
        }

        // Parent/main accounts are chart headings. The posting accounts underneath
        // carry the balances; including both can double count report totals.
        if (self::columnExists('accounts', 'is_main_account')) {
            $query->where('accounts.is_main_account', 0);
        }

        // Do not filter Accounts by accounts.location_id here. The movement
        // subquery above is already branch-scoped from the actual source
        // transaction/payment. A branch report must still show a posting when a
        // transaction from that branch used a globally shared or even historically
        // mis-assigned account master. accounts.location_id is only a last-resort
        // fallback for postings that have no source location at all.

        $hasAccountTypes = self::tableExists('account_types');
        $hasAccountGroups = self::tableExists('account_groups');
        $hasParentAccountType = $hasAccountTypes && self::columnExists('account_types', 'parent_account_type_id');
        $hasDefaultAccountType = $hasAccountTypes && self::columnExists('account_types', 'default_account_type_id');
        $hasGroupAccountType = $hasAccountGroups && self::columnExists('account_groups', 'account_type_id');
        $hasDefaultAccountGroup = $hasAccountGroups && self::columnExists('account_groups', 'default_account_group_id');

        if ($hasAccountTypes) {
            $query->leftJoin('account_types as fr_account_type', 'accounts.account_type_id', '=', 'fr_account_type.id');
            if ($hasParentAccountType) {
                $query->leftJoin('account_types as fr_parent_type', 'fr_account_type.parent_account_type_id', '=', 'fr_parent_type.id');
            }
        }

        if ($hasAccountGroups) {
            $query->leftJoin('account_groups as fr_account_group', 'accounts.asset_type', '=', 'fr_account_group.id');
            if ($hasGroupAccountType && $hasAccountTypes) {
                $query->leftJoin('account_types as fr_group_type', 'fr_account_group.account_type_id', '=', 'fr_group_type.id');
                if ($hasParentAccountType) {
                    $query->leftJoin('account_types as fr_group_parent_type', 'fr_group_type.parent_account_type_id', '=', 'fr_group_parent_type.id');
                }
            }
        }

        $select = [
            'accounts.id',
            'accounts.name',
            'accounts.account_number',
            'accounts.asset_type',
            'accounts.account_type_id',
        ];
        if (self::columnExists('accounts', 'show_in_balance_sheet')) {
            $select[] = 'accounts.show_in_balance_sheet';
        }
        if (self::columnExists('accounts', 'is_main_account')) {
            $select[] = 'accounts.is_main_account';
        }
        if (self::columnExists('accounts', 'location_id')) {
            $select[] = 'accounts.location_id as account_location_id';
        }

        $query->select($select)
            ->selectRaw($hasAccountTypes ? 'COALESCE(fr_account_type.name, "") as account_type_name' : '"" as account_type_name')
            ->selectRaw($hasParentAccountType ? 'COALESCE(fr_parent_type.name, "") as parent_account_type_name' : '"" as parent_account_type_name')
            ->selectRaw($hasParentAccountType ? 'COALESCE(fr_account_type.parent_account_type_id, 0) as parent_account_type_id' : '0 as parent_account_type_id')
            ->selectRaw($hasDefaultAccountType ? 'COALESCE(fr_account_type.default_account_type_id, 0) as default_account_type_id' : '0 as default_account_type_id')
            ->selectRaw(($hasDefaultAccountType && $hasParentAccountType) ? 'COALESCE(fr_parent_type.default_account_type_id, 0) as parent_default_account_type_id' : '0 as parent_default_account_type_id')
            ->selectRaw($hasAccountGroups ? 'COALESCE(fr_account_group.name, "") as account_group_name' : '"" as account_group_name')
            ->selectRaw($hasGroupAccountType ? 'COALESCE(fr_account_group.account_type_id, 0) as account_group_type_id' : '0 as account_group_type_id')
            ->selectRaw(($hasGroupAccountType && $hasAccountTypes) ? 'COALESCE(fr_group_type.name, "") as account_group_type_name' : '"" as account_group_type_name')
            ->selectRaw(($hasGroupAccountType && $hasAccountTypes && $hasParentAccountType) ? 'COALESCE(fr_group_parent_type.name, "") as account_group_parent_type_name' : '"" as account_group_parent_type_name')
            ->selectRaw(($hasGroupAccountType && $hasAccountTypes && $hasDefaultAccountType) ? 'COALESCE(fr_group_type.default_account_type_id, 0) as account_group_default_type_id' : '0 as account_group_default_type_id')
            ->selectRaw(($hasGroupAccountType && $hasAccountTypes && $hasDefaultAccountType && $hasParentAccountType) ? 'COALESCE(fr_group_parent_type.default_account_type_id, 0) as account_group_parent_default_type_id' : '0 as account_group_parent_default_type_id')
            ->selectRaw($hasDefaultAccountGroup ? 'COALESCE(fr_account_group.default_account_group_id, 0) as account_group_default_id' : '0 as account_group_default_id')
            ->selectRaw('COALESCE(fr_movement.debit, 0) as debit')
            ->selectRaw('COALESCE(fr_movement.credit, 0) as credit');

        if ($hasAccountGroups) {
            $query->orderBy('fr_account_group.name');
        }

        return $query->orderBy('accounts.name')->get();
    }

    private function transactionsQuery(int $business_id, ?string $start, ?string $end, $location_id)
    {
        $query = DB::table('account_transactions')
            ->leftJoin('accounts as scope_account', 'scope_account.id', '=', 'account_transactions.account_id')
            ->where(function ($transactionBusiness) use ($business_id) {
                $transactionBusiness->where('account_transactions.business_id', $business_id)
                    ->orWhere(function ($inherited) use ($business_id) {
                        $inherited->where(function ($missing) {
                            $missing->whereNull('account_transactions.business_id')
                                ->orWhere('account_transactions.business_id', 0);
                        })->where('scope_account.business_id', $business_id);
                    });
            });

        // Financial statements must ignore superseded/reversed accounting rows.
        $this->applyActiveAccountTransactionFilters($query, 'account_transactions');

        $transactionLocationColumn = null;
        if (self::tableExists('transactions')) {
            if (self::columnExists('transactions', 'location_id')) {
                $transactionLocationColumn = 'location_id';
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $transactionLocationColumn = 'business_location_id';
            }
        }

        $hasDirectTransaction = self::tableExists('transactions')
            && self::columnExists('account_transactions', 'transaction_id');
        $hasPaymentTransaction = self::tableExists('transaction_payments')
            && self::tableExists('transactions')
            && self::columnExists('account_transactions', 'transaction_payment_id')
            && self::columnExists('transaction_payments', 'transaction_id');

        if ($hasDirectTransaction) {
            $query->leftJoin('transactions as scope_direct_transaction', 'scope_direct_transaction.id', '=', 'account_transactions.transaction_id');
            $this->applyActiveTransactionFilters($query, 'scope_direct_transaction');
        }

        if ($hasPaymentTransaction) {
            $query->leftJoin('transaction_payments as scope_payment', 'scope_payment.id', '=', 'account_transactions.transaction_payment_id')
                ->leftJoin('transactions as scope_payment_transaction', 'scope_payment_transaction.id', '=', 'scope_payment.transaction_id');
            $this->applyActivePaymentFilters($query, 'scope_payment');
            $this->applyActiveTransactionFilters($query, 'scope_payment_transaction');
        }

        if ($start) {
            $query->where('account_transactions.operation_date', '>=', self::dayStart($start));
        }
        if ($end) {
            $query->where('account_transactions.operation_date', '<', self::dayAfter($end));
        }

        if ($location_id) {
            $hasAtLocation = self::columnExists('account_transactions', 'location_id');
            $hasDirectLocation = $hasDirectTransaction && $transactionLocationColumn !== null;
            $hasPaymentLocation = $hasPaymentTransaction && $transactionLocationColumn !== null;
            $hasAccountLocation = self::columnExists('accounts', 'location_id');

            if (!($hasAtLocation || $hasDirectLocation || $hasPaymentLocation || $hasAccountLocation)) {
                // A selected branch must never silently become a business-wide
                // report when the tenant schema has no resolvable location source.
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($location) use (
                    $location_id,
                    $hasAtLocation,
                    $hasDirectLocation,
                    $hasPaymentLocation,
                    $hasAccountLocation,
                    $transactionLocationColumn
                ) {
                    $location->whereRaw('1 = 0');

                    // 1) An explicit accounting-entry location wins when a tenant
                    // schema has that column.
                    if ($hasAtLocation) {
                        $location->orWhere('account_transactions.location_id', $location_id);
                    }

                    // 2) Otherwise use the directly linked source transaction.
                    if ($hasDirectLocation) {
                        $location->orWhere(function ($direct) use ($location_id, $hasAtLocation, $transactionLocationColumn) {
                            if ($hasAtLocation) {
                                $direct->where(function ($missingAt) {
                                    $missingAt->whereNull('account_transactions.location_id')
                                        ->orWhere('account_transactions.location_id', '');
                                });
                            }
                            $direct->where('scope_direct_transaction.' . $transactionLocationColumn, $location_id);
                        });
                    }

                    // 3) Payment-linked transaction location is next, but only if
                    // no higher-priority direct source location was available.
                    if ($hasPaymentLocation) {
                        $location->orWhere(function ($payment) use ($location_id, $hasAtLocation, $hasDirectLocation, $transactionLocationColumn) {
                            if ($hasAtLocation) {
                                $payment->where(function ($missingAt) {
                                    $missingAt->whereNull('account_transactions.location_id')
                                        ->orWhere('account_transactions.location_id', '');
                                });
                            }
                            if ($hasDirectLocation) {
                                $payment->whereNull('scope_direct_transaction.' . $transactionLocationColumn);
                            }
                            $payment->where('scope_payment_transaction.' . $transactionLocationColumn, $location_id);
                        });
                    }

                    // 4) Only truly unlinked rows may fall back to a branch-specific
                    // account master. Global 'all' accounts are never copied into
                    // every branch by assumption.
                    if ($hasAccountLocation) {
                        $location->orWhere(function ($accountLocation) use (
                            $location_id,
                            $hasAtLocation,
                            $hasDirectLocation,
                            $hasPaymentLocation,
                            $transactionLocationColumn
                        ) {
                            if ($hasAtLocation) {
                                $accountLocation->where(function ($missingAt) {
                                    $missingAt->whereNull('account_transactions.location_id')
                                        ->orWhere('account_transactions.location_id', '');
                                });
                            }
                            if ($hasDirectLocation) {
                                $accountLocation->whereNull('scope_direct_transaction.' . $transactionLocationColumn);
                            }
                            if ($hasPaymentLocation) {
                                $accountLocation->whereNull('scope_payment_transaction.' . $transactionLocationColumn);
                            }
                            $accountLocation->where('scope_account.location_id', (string) $location_id);
                        });
                    }
                });
            }
        }

        return $query;
    }

    private function contactStatementPaymentRows(int $business_id, string $contact_type, string $direction, int $contact_id, ?string $start, ?string $end, $location_id = null): Collection
    {
        if (!self::tableExists('transaction_payments')) {
            return collect();
        }

        $dateColumn = self::columnExists('transaction_payments', 'paid_on') ? 'paid_on' : 'created_at';
        $q = DB::table('transaction_payments as sp')
            ->join('transactions as st', 'sp.transaction_id', '=', 'st.id')
            ->where('st.business_id', $business_id)
            ->where(function ($transactionTypeScope) use ($direction) {
                $transactionTypeScope->where('st.type', $direction)
                    ->orWhere('st.type', 'opening_balance');
            })
            ->where('st.contact_id', $contact_id)
            ->where('st.status', '!=', 'draft');

        $this->applyActivePaymentFilters($q, 'sp');
        $this->applyActiveTransactionFilters($q, 'st');

        if ($start) { $q->where('sp.' . $dateColumn, '>=', self::dayStart($start)); }
        if ($end) { $q->where('sp.' . $dateColumn, '<', self::dayAfter($end)); }
        if ($location_id) {
            if (self::columnExists('transactions', 'location_id')) {
                $q->where('st.location_id', $location_id);
            } elseif (self::columnExists('transactions', 'business_location_id')) {
                $q->where('st.business_location_id', $location_id);
            } else {
                $q->whereRaw('1 = 0');
            }
        }

        return $q->orderBy('sp.' . $dateColumn)->orderBy('sp.id')->get([
            'sp.id',
            'sp.amount',
            'sp.payment_ref_no',
            'sp.method',
            'sp.' . $dateColumn . ' as event_date',
            'st.invoice_no',
            'st.ref_no',
        ])->map(function ($r) {
            $amount = round((float) $r->amount, 4);
            $reference = $r->payment_ref_no ?: ($r->invoice_no ?: ($r->ref_no ?: $r->id));
            return (object) [
                'transaction_date' => $r->event_date,
                'reference_no' => 'Payment: ' . $reference . ($r->method ? ' (' . ucfirst((string) $r->method) . ')' : ''),
                'final_total' => 0.0,
                'paid_amount' => $amount,
                'return_amount' => 0.0,
                'balance' => -$amount,
                'event_order' => 20,
            ];
        });
    }

    private function contactStatementReturnRows(int $business_id, string $direction, int $contact_id, ?string $start, ?string $end, $location_id = null): Collection
    {
        if (!self::columnExists('transactions', 'return_parent_id')) {
            return collect();
        }

        $returnType = $direction === 'purchase' ? 'purchase_return' : 'sell_return';
        $dateColumn = self::columnExists('transactions', 'transaction_date') ? 'transaction_date' : 'created_at';
        $q = DB::table('transactions as rt')
            ->join('transactions as src', 'rt.return_parent_id', '=', 'src.id')
            ->where('src.business_id', $business_id)
            ->where('src.type', $direction)
            ->where('src.contact_id', $contact_id)
            ->where('rt.type', $returnType);

        $this->applyActiveTransactionFilters($q, 'rt');
        $this->applyActiveTransactionFilters($q, 'src');

        if ($start) { $q->where('rt.' . $dateColumn, '>=', self::dayStart($start)); }
        if ($end) { $q->where('rt.' . $dateColumn, '<', self::dayAfter($end)); }
        if ($location_id) {
            $locationColumn = self::columnExists('transactions', 'location_id')
                ? 'location_id'
                : (self::columnExists('transactions', 'business_location_id') ? 'business_location_id' : null);
            if ($locationColumn) {
                $q->where(function ($loc) use ($location_id, $locationColumn) {
                    $loc->where('rt.' . $locationColumn, $location_id)
                        ->orWhere(function ($sourceFallback) use ($location_id, $locationColumn) {
                            $sourceFallback->where(function ($missingReturnLocation) use ($locationColumn) {
                                $missingReturnLocation->whereNull('rt.' . $locationColumn)
                                    ->orWhere('rt.' . $locationColumn, 0);
                            })->where('src.' . $locationColumn, $location_id);
                        });
                });
            } else {
                $q->whereRaw('1 = 0');
            }
        }

        return $q->orderBy('rt.' . $dateColumn)->orderBy('rt.id')->get([
            'rt.id',
            'rt.final_total',
            'rt.invoice_no',
            'rt.ref_no',
            'rt.' . $dateColumn . ' as event_date',
        ])->map(function ($r) {
            $amount = round((float) $r->final_total, 4);
            $reference = $r->invoice_no ?: ($r->ref_no ?: $r->id);
            return (object) [
                'transaction_date' => $r->event_date,
                'reference_no' => 'Return / Credit: ' . $reference,
                'final_total' => 0.0,
                'paid_amount' => 0.0,
                'return_amount' => $amount,
                'balance' => -$amount,
                'event_order' => 30,
            ];
        });
    }

    private function transactionReturnExpression(string $direction, ?string $asAt = null, $location_id = null): string
    {
        if (!self::tableExists('transactions') || !self::columnExists('transactions', 'return_parent_id') || !self::columnExists('transactions', 'final_total')) {
            return '0';
        }

        $returnType = $direction === 'purchase' ? 'purchase_return' : 'sell_return';
        $conditions = [
            'rt.return_parent_id = transactions.id',
            "rt.type = '" . addslashes($returnType) . "'",
        ];

        if (self::columnExists('transactions', 'deleted_at')) {
            $conditions[] = 'rt.deleted_at IS NULL';
        }
        if (self::columnExists('transactions', 'new_deleted_at')) {
            $conditions[] = 'rt.new_deleted_at IS NULL';
        }
        if ($asAt) {
            $dateColumn = self::columnExists('transactions', 'transaction_date') ? 'transaction_date' : 'created_at';
            $conditions[] = 'rt.' . $dateColumn . " < '" . addslashes((string) self::dayAfter($asAt)) . "'";
        }

        if ($location_id) {
            $locationColumn = self::columnExists('transactions', 'location_id')
                ? 'location_id'
                : (self::columnExists('transactions', 'business_location_id') ? 'business_location_id' : null);

            if ($locationColumn) {
                $resolvedLocation = addslashes((string) $location_id);
                // The source invoice has already been scoped to the selected
                // location. Prefer the return's own location; only inherit the
                // parent invoice location when the return itself has no location.
                // A return explicitly processed at another branch must not reduce
                // this branch's receivable/payable balance.
                $conditions[] = "(rt.{$locationColumn} = '{$resolvedLocation}' OR rt.{$locationColumn} IS NULL OR rt.{$locationColumn} = 0)";
            } else {
                // Branch-specific return allocation cannot be proven. Do not
                // silently subtract business-wide returns from every branch.
                $conditions[] = '1 = 0';
            }
        }

        return '(SELECT COALESCE(SUM(rt.final_total),0) FROM transactions rt WHERE ' . implode(' AND ', $conditions) . ')';
    }

    /**
     * Classify actual movement on Cash/Bank accounts by their paired/related
     * account. The sign is from the cash account itself: debit=inflow,
     * credit=outflow.
     */

    private function cashFlowMovementsFromCashAccounts(int $business_id, ?string $start, ?string $end, $location_id = null): array
    {
        $empty = [
            'Operating Activities' => collect(),
            'Investing Activities' => collect(),
            'Financing Activities' => collect(),
        ];

        if (!self::tableExists('account_transactions') || !self::tableExists('accounts')) {
            return $empty;
        }

        $cashAccounts = $this->accountsByPurpose($business_id, ['cash', 'bank']);
        $cashIds = $cashAccounts->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
        if (!$cashIds) {
            return $empty;
        }

        $q = $this->transactionsQuery($business_id, $start, $end, $location_id)
            ->whereIn('account_transactions.account_id', $cashIds);

        if (self::columnExists('account_transactions', 'pair_at_id')) {
            $q->leftJoin('account_transactions as cf_pair_at', 'cf_pair_at.id', '=', 'account_transactions.pair_at_id')
                ->leftJoin('accounts as cf_pair_account', 'cf_pair_account.id', '=', 'cf_pair_at.account_id');
        }
        if (self::columnExists('account_transactions', 'related_account_id')) {
            $q->leftJoin('accounts as cf_related_account', 'cf_related_account.id', '=', 'account_transactions.related_account_id');
        }

        if (self::tableExists('account_types')) {
            if (self::columnExists('account_transactions', 'pair_at_id')) {
                $q->leftJoin('account_types as cf_pair_type', 'cf_pair_account.account_type_id', '=', 'cf_pair_type.id')
                    ->leftJoin('account_types as cf_pair_parent', 'cf_pair_type.parent_account_type_id', '=', 'cf_pair_parent.id');
            }
            if (self::columnExists('account_transactions', 'related_account_id')) {
                $q->leftJoin('account_types as cf_related_type', 'cf_related_account.account_type_id', '=', 'cf_related_type.id')
                    ->leftJoin('account_types as cf_related_parent', 'cf_related_type.parent_account_type_id', '=', 'cf_related_parent.id');
            }
        }

        $select = [
            'account_transactions.id',
            'account_transactions.type',
            'account_transactions.amount',
        ];
        if (self::columnExists('account_transactions', 'pair_at_id')) {
            $select[] = 'cf_pair_account.name as pair_name';
            if (self::tableExists('account_types')) {
                $select[] = 'cf_pair_type.name as pair_type';
                $select[] = 'cf_pair_parent.name as pair_root';
            }
        }
        if (self::columnExists('account_transactions', 'related_account_id')) {
            $select[] = 'cf_related_account.name as related_name';
            if (self::tableExists('account_types')) {
                $select[] = 'cf_related_type.name as related_type';
                $select[] = 'cf_related_parent.name as related_root';
            }
        }

        $rows = $q->get($select);
        $grouped = [
            'Operating Activities' => collect(),
            'Investing Activities' => collect(),
            'Financing Activities' => collect(),
        ];

        foreach ($rows as $r) {
            $name = trim((string) (($r->pair_name ?? '') ?: ($r->related_name ?? '')));
            $type = trim((string) (($r->pair_type ?? '') ?: ($r->related_type ?? '')));
            $root = trim((string) (($r->pair_root ?? '') ?: ($r->related_root ?? $type)));
            $text = strtolower($root . ' ' . $type . ' ' . $name);

            $section = 'Operating Activities';
            if (strtolower($root) === 'assets' && (str_contains(strtolower($type), 'fixed') || preg_match('/fixed|property|plant|equipment|vehicle|machinery/', $text))) {
                $section = 'Investing Activities';
            } elseif (strtolower($root) === 'equity' || preg_match('/loan|borrow|long.?term|capital|owner.?contribution/', $text)) {
                $section = 'Financing Activities';
            }

            $amount = ($r->type ?? '') === 'debit' ? (float) $r->amount : -(float) $r->amount;
            $line = $name !== '' ? $name : 'Unclassified cash movement';

            $existing = $grouped[$section]->firstWhere('line', $line);
            if ($existing) {
                $existing->amount = round((float) $existing->amount + $amount, 4);
            } else {
                $grouped[$section]->push((object) ['line' => $line, 'amount' => round($amount, 4)]);
            }
        }

        foreach ($grouped as $section => $items) {
            $grouped[$section] = $items->filter(fn ($r) => abs((float) $r->amount) > 0.0001)->values();
        }

        return $grouped;
    }

    private function accountsByPurpose(int $business_id, array $keywords): Collection
    {
        $normalized = collect($keywords)->map(fn ($k) => strtolower(trim((string) $k)))->filter()->unique()->values();
        $cashRequested = $normalized->contains('cash');
        $bankRequested = $normalized->contains('bank');

        $query = DB::table('accounts')
            ->where('accounts.business_id', $business_id);

        if (self::columnExists('accounts', 'deleted_at')) {
            $query->whereNull('accounts.deleted_at');
        }

        if (($cashRequested || $bankRequested) && self::tableExists('account_groups')) {
            $query->leftJoin('account_groups as purpose_group', 'accounts.asset_type', '=', 'purpose_group.id')
                ->where(function ($q) use ($cashRequested, $bankRequested) {
                    if ($cashRequested) {
                        $q->orWhere(function ($cash) {
                            if (self::columnExists('account_groups', 'default_account_group_id')) {
                                $cash->where('purpose_group.default_account_group_id', 5);
                            } else {
                                $cash->whereRaw('LOWER(purpose_group.name) = ?', ['cash account']);
                            }
                            $cash->orWhereRaw('LOWER(accounts.name) IN (?, ?)', ['cash', 'petty cash']);
                        });
                    }
                    if ($bankRequested) {
                        $q->orWhere(function ($bank) {
                            if (self::columnExists('account_groups', 'default_account_group_id')) {
                                $bank->where('purpose_group.default_account_group_id', 4);
                            } else {
                                $bank->whereRaw('LOWER(purpose_group.name) = ?', ['bank account']);
                            }
                            if (self::columnExists('accounts', 'is_business_bank_account')) {
                                $bank->orWhere('accounts.is_business_bank_account', 1);
                            }
                        });
                    }
                });
        } else {
            $query->where(function ($q) use ($normalized) {
                foreach ($normalized as $keyword) {
                    $q->orWhere('accounts.name', 'like', '%' . $keyword . '%')
                        ->orWhere('accounts.account_number', 'like', '%' . $keyword . '%');
                }
            });
        }

        return $query->orderBy('accounts.name')->get([
            'accounts.id',
            'accounts.name',
            'accounts.account_number',
        ]);
    }

    /**
     * Returns sale/purchase return amounts linked to the source invoice, as at
     * the selected reporting date. Returns reduce receivable/payable exposure.
     */

    private function applyActiveAccountTransactionFilters($query, string $alias = 'account_transactions'): void
    {
        if (self::columnExists('account_transactions', 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }
        if (self::columnExists('account_transactions', 'new_deleted_at')) {
            $query->whereNull($alias . '.new_deleted_at');
        }
        if (self::columnExists('account_transactions', 'reversed')) {
            $query->where(function ($q) use ($alias) {
                $q->whereNull($alias . '.reversed')->orWhere($alias . '.reversed', 0);
            });
        }
        if (self::columnExists('account_transactions', 'journal_deleted')) {
            $query->where(function ($q) use ($alias) {
                $q->whereNull($alias . '.journal_deleted')->orWhere($alias . '.journal_deleted', 0);
            });
        }
    }

    private function applyActiveTransactionFilters($query, string $alias = 'transactions'): void
    {
        if (self::columnExists('transactions', 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }
        if (self::columnExists('transactions', 'new_deleted_at')) {
            $query->whereNull($alias . '.new_deleted_at');
        }
    }

    private function applyActivePaymentFilters($query, string $alias = 'transaction_payments'): void
    {
        if (self::columnExists('transaction_payments', 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }
        if (self::columnExists('transaction_payments', 'is_return')) {
            $query->where(function ($q) use ($alias) {
                $q->whereNull($alias . '.is_return')->orWhere($alias . '.is_return', 0);
            });
        }
    }

    private function normalSide(object $account): string
    {
        if (in_array($this->accountCategory($account), ['Liabilities', 'Income', 'Equity'], true)) {
            return 'credit';
        }
        return 'debit';
    }

    private function balanceSheetSection(object $account): string
    {
        $category = $this->accountCategory($account);
        return in_array($category, ['Assets', 'Liabilities', 'Equity'], true) ? $category : 'Other';
    }

    private function plSection(object $account): string
    {
        $category = $this->accountCategory($account);
        return in_array($category, ['Income', 'Expenses'], true) ? $category : 'Other';
    }

    /**
     * Resolve the accounting category from configured Account Type hierarchy.
     *
     * IMPORTANT: account/group/display-name keywords are deliberately NOT used
     * when a type exists. In the production chart of accounts, Accounts
     * Receivable is a Current Asset whose group is named "Credit Sales". The old
     * keyword concatenation saw "Sales" and moved that asset into P&L Income.
     */
    private function accountCategory(object $account): string
    {
        $category = $this->categoryFromTypeMetadata(
            (int)($account->default_account_type_id ?? 0),
            (int)($account->parent_default_account_type_id ?? 0),
            (string)($account->account_type_name ?? ''),
            (string)($account->parent_account_type_name ?? '')
        );

        if ($category !== 'Other') {
            return $category;
        }

        // Fallback only when the account itself has no usable type. Account
        // groups also carry an account_type_id in the Finance chart of accounts.
        return $this->categoryFromTypeMetadata(
            (int)($account->account_group_default_type_id ?? 0),
            (int)($account->account_group_parent_default_type_id ?? 0),
            (string)($account->account_group_type_name ?? ''),
            (string)($account->account_group_parent_type_name ?? '')
        );
    }

    private function categoryFromTypeMetadata(int $defaultTypeId, int $parentDefaultTypeId, string $typeName, string $parentTypeName): string
    {
        $rootId = $parentDefaultTypeId > 0 ? $parentDefaultTypeId : $defaultTypeId;
        $idMap = [
            1 => 'Assets',
            2 => 'Liabilities',
            3 => 'Income',
            4 => 'Expenses',
            5 => 'Equity',
        ];
        if (isset($idMap[$rootId])) {
            return $idMap[$rootId];
        }

        $rootName = strtolower(trim($parentTypeName !== '' ? $parentTypeName : $typeName));
        if ($rootName === '') {
            return 'Other';
        }
        if (str_contains($rootName, 'asset')) {
            return 'Assets';
        }
        if (str_contains($rootName, 'liabil')) {
            return 'Liabilities';
        }
        if (str_contains($rootName, 'income') || str_contains($rootName, 'revenue')) {
            return 'Income';
        }
        if (str_contains($rootName, 'expense')) {
            return 'Expenses';
        }
        if (str_contains($rootName, 'equity') || str_contains($rootName, 'capital')) {
            return 'Equity';
        }

        return 'Other';
    }

    private function isCurrentBalanceSheetAccount(object $account): bool
    {
        $category = $this->accountCategory($account);
        $directDefault = (int)($account->default_account_type_id ?? 0);
        $groupDefault = (int)($account->account_group_default_type_id ?? 0);
        $directName = strtolower((string)($account->account_type_name ?? ''));
        $groupTypeName = strtolower((string)($account->account_group_type_name ?? ''));

        if ($category === 'Assets') {
            return $directDefault === 6
                || $groupDefault === 6
                || str_contains($directName, 'current asset')
                || str_contains($groupTypeName, 'current asset');
        }

        if ($category === 'Liabilities') {
            return $directDefault === 8
                || $groupDefault === 8
                || str_contains($directName, 'current liabil')
                || str_contains($groupTypeName, 'current liabil');
        }

        return false;
    }

    private function isRevenueAccount(object $account): bool
    {
        if ($this->accountCategory($account) !== 'Income') {
            return false;
        }

        // Default account group 9 is the system's Sales Income Group. The name
        // fallback covers the base Sales Income account, which can legitimately
        // have no account_group/asset_type assigned.
        if (in_array((int)($account->account_group_default_id ?? 0), [9, 33], true)) {
            return true;
        }

        $text = strtolower(($account->account_group_name ?? '') . ' ' . ($account->name ?? ''));
        return str_contains($text, 'sales income')
            || str_contains($text, 'sales revenue')
            || str_contains($text, 'contra revenue')
            || trim($text) === 'sales';
    }

    private function isCogsAccount(object $account): bool
    {
        if ($this->accountCategory($account) !== 'Expenses') {
            return false;
        }

        if ((int)($account->account_group_default_id ?? 0) === 8) {
            return true;
        }

        $text = strtolower(($account->account_group_name ?? '') . ' ' . ($account->name ?? ''));
        return str_contains($text, 'cogs') || str_contains($text, 'cost of goods sold') || str_contains($text, 'cost of goods');
    }
}
