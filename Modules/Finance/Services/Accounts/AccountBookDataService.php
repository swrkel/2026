<?php

namespace Modules\Finance\Services\Accounts;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fast Finance-owned Account Book reader.
 *
 * The first query touches only account_transactions. Related records are
 * loaded in small batches for the requested page, avoiding the large joined
 * count/query that previously left the DataTable in Processing state.
 */
class AccountBookDataService
{
    /** @var array<string, bool> */
    private static array $columnCache = [];

    public function datatable(int $accountId, int $businessId, Request $request): array
    {
        $account = DB::table('accounts as a')
            ->leftJoin('account_types as account_type', 'account_type.id', '=', 'a.account_type_id')
            ->where('a.business_id', $businessId)
            ->where('a.id', $accountId)
            ->select([
                'a.id',
                'a.name',
                'a.parent_account_id',
                'a.asset_type',
                'a.location_id',
                'account_type.name as account_type_name',
            ])
            ->first();

        abort_if(! $account, 404, 'Account not found.');

        $draw = max(0, (int) $request->input('draw', 0));
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        // DataTables uses -1 for its "All" entries option. Preserve that
        // value so Account Books can genuinely return all filtered rows when
        // the user explicitly selects All (including for full exports).
        if ($length === -1) {
            $length = -1;
        } else {
            $length = $length < 1 ? 25 : min($length, 100);
        }

        $accountIds = $this->accountIds($account, $businessId);
        $locationId = $this->effectiveLocationId($account, $request);
        $normalDebitBalance = $this->isDebitNormal((string) ($account->account_type_name ?? ''));

        $filtered = $this->transactionQuery($accountIds, $businessId, $locationId);
        $this->applyFilters($filtered, $request);

        // Resolve count and totals in one indexed aggregate query. This avoids
        // three full scans before the first 25 rows can be returned.
        $summary = (clone $filtered)
            ->selectRaw('COUNT(at.id) AS row_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN at.type = 'debit' THEN at.amount ELSE 0 END), 0) AS debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN at.type = 'credit' THEN at.amount ELSE 0 END), 0) AS credit_total")
            ->first();

        $recordsFiltered = (int) ($summary->row_count ?? 0);

        $startDate = $this->dateInput($request->input('start_date'));
        $openingBalance = $this->openingBalance(
            $accountIds,
            $businessId,
            $locationId,
            $startDate,
            $normalDebitBalance
        );
        $pageOpening = $openingBalance + $this->signedBalanceBeforePage($filtered, $start, $normalDebitBalance);

        // Join related display values only after the account/date filters are
        // established. For a 25-row page this replaces four extra lookup
        // round-trips with one indexed query.
        $rowsQuery = (clone $filtered)
            ->leftJoin('transactions as row_transaction', 'row_transaction.id', '=', 'at.transaction_id')
            ->leftJoin('contacts as row_contact', 'row_contact.id', '=', 'row_transaction.contact_id')
            ->when($this->hasJournalLink(), function ($query): void {
                /*
                 * IS2034 #3: journal rows have no parent transaction, so the number
                 * that identifies them lives on journals.journal_id. Joined here with
                 * the other display-only lookups - after the account/date filters are
                 * settled - so it cannot affect which rows are selected.
                 */
                $query->leftJoin('journals as row_journal', 'row_journal.id', '=', 'at.journal_entry');
            })
            ->select($this->rowSelectColumns())
            // IS2258 #1: Account Book order follows the first visible
            // Date & Time column (system entry timestamp), oldest first.
            // created_at is authoritative for entry time; operation_date is
            // only the legacy fallback when created_at is unavailable.
            ->orderByRaw("COALESCE(at.created_at, at.operation_date, '9999-12-31 23:59:59') ASC")
            ->orderBy('at.id', 'asc');

        if ($length !== -1) {
            $rowsQuery->offset($start)->limit($length);
        }

        $rows = $rowsQuery->get();

        $precision = $this->currencyPrecision($businessId);
        $hiddenDescriptionLines = $this->accountBookHiddenDescriptionLines($businessId, $locationId);
        $runningBalance = $pageOpening;
        $data = [];

        foreach ($rows as $row) {
            $transaction = ! empty($row->transaction_id) ? (object) [
                'type' => $row->transaction_type,
                'sub_type' => $row->transaction_sub_type,
                'transaction_date' => $row->transaction_date,
                'invoice_no' => $row->invoice_no,
                'ref_no' => $row->ref_no,
                'contact_id' => $row->contact_id,
            ] : null;
            $payment = ! empty($row->transaction_payment_id) ? (object) [
                'transaction_id' => $row->payment_transaction_id,
                'payment_for' => $row->payment_for,
                'payment_for_type' => $row->payment_for_type,
                'payment_for_name' => $row->payment_for_name,
                'cheque_number' => $row->payment_cheque_number,
                'cheque_date' => $row->payment_cheque_date,
                'paid_on' => $row->payment_paid_on,
                'payment_ref_no' => $row->payment_ref_no,
                'parent_id' => $row->payment_parent_id,
                'card_number' => $row->payment_card_number,
                'card_type' => $row->payment_card_type,
                'bank_name' => $row->payment_bank_name,
                'note' => $row->payment_note,
            ] : null;
            $contactName = ! empty($row->contact_name)
                ? $row->contact_name
                : ($row->payment_for_name ?? null);
            $contact = ! empty($contactName) ? (object) ['name' => $contactName] : null;
            $amount = (float) ($row->amount ?? 0);
            // The Opening Balance column is the balance immediately before
            // this transaction. This makes the column meaningful on every row
            // instead of repeating one period-opening value throughout.
            $rowOpeningBalance = $runningBalance;
            $runningBalance += $this->signedAmount((string) $row->type, $amount, $normalDebitBalance);

            $data[] = $this->formatRow(
                $row,
                $transaction,
                $payment,
                $contact,
                $rowOpeningBalance,
                $runningBalance,
                $precision,
                $hiddenDescriptionLines
            );
        }

        $debitTotal = (float) ($summary->debit_total ?? 0);
        $creditTotal = (float) ($summary->credit_total ?? 0);
        $filteredSigned = $normalDebitBalance
            ? ($debitTotal - $creditTotal)
            : ($creditTotal - $debitTotal);

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsFiltered,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'scope' => [
                'business_id' => $businessId,
                'location_id' => $locationId,
            ],
            'totals' => [
                'debit' => $debitTotal,
                'credit' => $creditTotal,
                'opening_balance' => $openingBalance,
                'ending_balance' => $openingBalance + $filteredSigned,
            ],
        ];
    }

    private function accountIds(object $account, int $businessId): array
    {
        $ids = [(int) $account->id];

        if (stripos((string) $account->name, 'Cards (Credit Debit) Account') !== false) {
            $children = DB::table('accounts')
                ->where('business_id', $businessId)
                ->where('parent_account_id', $account->id)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $ids = array_values(array_unique(array_merge($ids, $children)));
        }

        return $ids;
    }

    private function transactionQuery(array $accountIds, int $businessId, ?int $locationId): Builder
    {
        /*
         * Business and location are scoped by the selected account records:
         * - datatable() first validates the requested account belongs to the
         *   logged-in business;
         * - accountIds() only adds child accounts from that same business;
         * - each account is the application's location-owned ledger bucket.
         *
         * Do not join the full transactions table here. That join previously
         * forced MySQL to examine unrelated business/location rows before it
         * could return the first Account Book page.
         */
        $query = DB::table('account_transactions as at')
            ->leftJoin('transaction_payments as scope_payment', 'scope_payment.id', '=', 'at.transaction_payment_id')
            // S763: Supplier Pay Due root rows intentionally have no parent
            // transaction. payment_for is therefore the reliable way to know
            // that the ledger row belongs to a supplier payment.
            ->leftJoin('contacts as scope_payment_contact', 'scope_payment_contact.id', '=', 'scope_payment.payment_for')
            ->whereIn('at.account_id', $accountIds)
            ->whereNull('at.deleted_at')
            ->where(function (Builder $paymentQuery): void {
                $paymentQuery->whereNull('at.transaction_payment_id')
                    ->orWhereNull('scope_payment.deleted_at');
            });

        // Some tenant databases store location_id directly on ledger rows.
        // Apply it only when present; null/zero legacy entries remain valid
        // because their account_id already belongs to the selected location.
        if ($locationId && $this->hasColumn('account_transactions', 'location_id')) {
            $query->where(function (Builder $locationQuery) use ($locationId): void {
                $locationQuery->where('at.location_id', $locationId)
                    ->orWhereNull('at.location_id')
                    ->orWhere('at.location_id', 0);
            });
        }

        return $query->where(function (Builder $amountQuery): void {
            $amountQuery->whereNull('at.amount')
                ->orWhereRaw('ABS(at.amount) > 0.001')
                ->orWhere('at.sub_type', 'expense_reverse');
        });
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $startDate = $this->dateInput($request->input('start_date'));
        $endDate = $this->dateInput($request->input('end_date'));
        $dateBasedOn = (string) $request->input('date_based_on', 'transaction_date');

        if ($startDate && $endDate) {
            if ($dateBasedOn === 'cheque_date') {
                $query->where(function (Builder $dateQuery) use ($startDate, $endDate): void {
                    $dateQuery->whereBetween('at.cheque_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->orWhereExists(function ($paymentQuery) use ($startDate, $endDate): void {
                            $paymentQuery->selectRaw('1')
                                ->from('transaction_payments as date_payment')
                                ->whereColumn('date_payment.id', 'at.transaction_payment_id')
                                ->whereBetween('date_payment.cheque_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        })
                        ->orWhere(function (Builder $depositQuery) use ($startDate, $endDate): void {
                            $depositQuery->where('at.sub_type', 'deposit')
                                ->whereBetween('at.operation_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                        });
                });
            } else {
                // S763: Supplier payments are dated by the user's selected
                // paid_on value. Keep historical rows filterable by that same
                // Transaction Date even when an older account_transaction kept
                // its system-entry operation_date. Other ledger rows retain the
                // existing operation_date filter unchanged.
                $query->whereBetween(
                    DB::raw("COALESCE(CASE WHEN scope_payment_contact.type IN ('supplier','both') THEN scope_payment.paid_on END, at.operation_date)"),
                    [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
                );
            }
        }

        if ($type = trim((string) $request->input('type'))) {
            $query->where('at.type', $type);
        }

        if (($amount = $request->input('amount')) !== null && $amount !== '') {
            $numericAmount = (float) str_replace(',', '', (string) $amount);
            $query->whereBetween('at.amount', [$numericAmount - 0.0001, $numericAmount + 0.0001]);
        }

        if ($slip = trim((string) $request->input('slip_no'))) {
            $query->where('at.slip_no', $slip);
        }

        $customer = (int) $request->input('customer');
        $supplier = (int) $request->input('supplier');
        if ($customer > 0) {
            $query->whereExists(function ($transactionQuery) use ($customer): void {
                $transactionQuery->selectRaw('1')
                    ->from('transactions as contact_transaction')
                    ->whereColumn('contact_transaction.id', 'at.transaction_id')
                    ->where('contact_transaction.contact_id', $customer);
            });
        } elseif ($supplier > 0) {
            // S766: Supplier Pay Due root account_transactions intentionally
            // have no parent transaction_id. The previous supplier filter only
            // looked through transactions.contact_id, which hid the newly-added
            // Pay Due payment from Account Book whenever Supplier was selected.
            $query->where(function (Builder $supplierQuery) use ($supplier): void {
                $supplierQuery->where('scope_payment.payment_for', $supplier)
                    ->orWhereExists(function ($transactionQuery) use ($supplier): void {
                        $transactionQuery->selectRaw('1')
                            ->from('transactions as contact_transaction')
                            ->whereColumn('contact_transaction.id', 'at.transaction_id')
                            ->where('contact_transaction.contact_id', $supplier);
                    });
            });
        }

        $customerCheque = trim((string) $request->input('customer_cheque_no'));
        $chequeNumber = trim((string) $request->input('cheque_number'));
        $cheque = $customerCheque !== '' ? $customerCheque : $chequeNumber;
        if ($cheque !== '') {
            $query->where(function (Builder $chequeQuery) use ($cheque): void {
                $chequeQuery->where('at.cheque_number', $cheque)
                    ->orWhereExists(function ($paymentQuery) use ($cheque): void {
                        $paymentQuery->selectRaw('1')
                            ->from('transaction_payments as cheque_payment')
                            ->whereColumn('cheque_payment.id', 'at.transaction_payment_id')
                            ->where('cheque_payment.cheque_number', $cheque);
                    });
            });
        }

        if ($cardType = trim((string) $request->input('card_type'))) {
            $query->whereExists(function ($paymentQuery) use ($cardType): void {
                $paymentQuery->selectRaw('1')
                    ->from('transaction_payments as card_payment')
                    ->whereColumn('card_payment.id', 'at.transaction_payment_id')
                    ->where('card_payment.card_type', $cardType);
            });
        }

        $search = trim((string) data_get($request->input('search', []), 'value', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $hasPaymentNote = $this->hasColumn('transaction_payments', 'note');
            $query->where(function (Builder $searchQuery) use ($like, $hasPaymentNote): void {
                $searchQuery->where('at.note', 'like', $like)
                    ->orWhere('at.slip_no', 'like', $like)
                    ->orWhere('at.cheque_number', 'like', $like)
                    ->orWhereRaw('CAST(at.amount AS CHAR) LIKE ?', [$like])
                    ->orWhereExists(function ($transactionQuery) use ($like): void {
                        $transactionQuery->selectRaw('1')
                            ->from('transactions as search_transaction')
                            ->leftJoin('contacts as search_contact', 'search_contact.id', '=', 'search_transaction.contact_id')
                            ->whereColumn('search_transaction.id', 'at.transaction_id')
                            ->where(function (Builder $inner) use ($like): void {
                                $inner->where('search_transaction.invoice_no', 'like', $like)
                                    ->orWhere('search_transaction.ref_no', 'like', $like)
                                    ->orWhere('search_contact.name', 'like', $like);
                            });
                    })
                    ->orWhereExists(function ($paymentQuery) use ($like, $hasPaymentNote): void {
                        $paymentQuery->selectRaw('1')
                            ->from('transaction_payments as search_payment')
                            ->whereColumn('search_payment.id', 'at.transaction_payment_id')
                            ->where(function (Builder $inner) use ($like, $hasPaymentNote): void {
                                $inner->where('search_payment.cheque_number', 'like', $like)
                                    ->orWhere('search_payment.payment_ref_no', 'like', $like);

                                if ($hasPaymentNote) {
                                    $inner->orWhere('search_payment.note', 'like', $like);
                                }
                            });
                    });
            });
        }
    }

    private function openingBalance(
        array $accountIds,
        int $businessId,
        ?int $locationId,
        ?string $startDate,
        bool $normalDebitBalance
    ): float
    {
        if (! $startDate) {
            return 0.0;
        }

        $row = $this->transactionQuery($accountIds, $businessId, $locationId)
            ->where('at.operation_date', '<', $startDate . ' 00:00:00')
            ->selectRaw("COALESCE(SUM(CASE WHEN at.type = 'debit' THEN at.amount ELSE 0 END), 0) AS debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN at.type = 'credit' THEN at.amount ELSE 0 END), 0) AS credit_total")
            ->first();

        return $this->netBalance(
            (float) ($row->debit_total ?? 0),
            (float) ($row->credit_total ?? 0),
            $normalDebitBalance
        );
    }

    private function signedBalanceBeforePage(Builder $filtered, int $start, bool $normalDebitBalance): float
    {
        if ($start <= 0) {
            return 0.0;
        }

        $entryTimestamp = "COALESCE(at.created_at, at.operation_date, '9999-12-31 23:59:59')";

        $boundary = (clone $filtered)
            ->select(['at.operation_date', 'at.created_at', 'at.id'])
            ->selectRaw($entryTimestamp . ' AS finance_entry_timestamp')
            ->orderByRaw($entryTimestamp . ' ASC')
            ->orderBy('at.id', 'asc')
            ->offset($start)
            ->limit(1)
            ->first();

        if (! $boundary) {
            return 0.0;
        }

        $boundaryTimestamp = (string) ($boundary->finance_entry_timestamp ?? '');

        $prior = (clone $filtered)
            ->where(function (Builder $query) use ($boundary, $boundaryTimestamp, $entryTimestamp): void {
                $query->whereRaw($entryTimestamp . ' < ?', [$boundaryTimestamp])
                    ->orWhere(function (Builder $sameDate) use ($boundary, $boundaryTimestamp, $entryTimestamp): void {
                        $sameDate->whereRaw($entryTimestamp . ' = ?', [$boundaryTimestamp])
                            ->where('at.id', '<', $boundary->id);
                    });
            })
            ->selectRaw("COALESCE(SUM(CASE WHEN at.type = 'debit' THEN at.amount ELSE 0 END), 0) AS debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN at.type = 'credit' THEN at.amount ELSE 0 END), 0) AS credit_total")
            ->first();

        return $this->netBalance(
            (float) ($prior->debit_total ?? 0),
            (float) ($prior->credit_total ?? 0),
            $normalDebitBalance
        );
    }

    private function effectiveLocationId(object $account, Request $request): ?int
    {
        // The account itself is the source of truth for location scope.
        // A global/all-location account must not be silently restricted by the
        // user's currently selected location, otherwise valid ledger rows can
        // disappear from the Account Book.
        $candidates = [
            $account->location_id ?? null,
            $request->input('location_id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return null;
    }


    /** @return array<int, mixed> */
    private function rowSelectColumns(): array
    {
        return [
            'at.id',
            'at.account_id',
            'at.type',
            'at.amount',
            'at.operation_date',
            'at.created_at',
            'at.note',
            'at.sub_type',
            'at.new_deleted_at',
            'at.slip_no',
            'at.cheque_number',
            'at.cheque_date',
            $this->hasColumn('account_transactions', 'reconcile_status')
                ? 'at.reconcile_status'
                : DB::raw('0 as reconcile_status'),
            'at.transaction_id',
            'at.transaction_payment_id',
            'scope_payment.transaction_id as payment_transaction_id',
            'scope_payment.payment_for',
            'scope_payment_contact.type as payment_for_type',
            'scope_payment_contact.name as payment_for_name',
            'row_transaction.type as transaction_type',
            'row_transaction.sub_type as transaction_sub_type',
            'row_transaction.transaction_date',
            'row_transaction.invoice_no',
            'row_transaction.ref_no',
            'row_transaction.contact_id',
            'row_contact.name as contact_name',
            'scope_payment.cheque_number as payment_cheque_number',
            'scope_payment.cheque_date as payment_cheque_date',
            'scope_payment.paid_on as payment_paid_on',
            'scope_payment.payment_ref_no',
            $this->hasColumn('transaction_payments', 'parent_id')
                ? 'scope_payment.parent_id as payment_parent_id'
                : DB::raw('NULL as payment_parent_id'),
            'scope_payment.card_number as payment_card_number',
            'scope_payment.card_type as payment_card_type',
            'scope_payment.bank_name as payment_bank_name',
            $this->hasColumn('transaction_payments', 'note')
                ? 'scope_payment.note as payment_note'
                : DB::raw("'' as payment_note"),
            // IS2034 #3: null on tenants without the journal link, so
            // transactionNumber() simply finds nothing to fall back to.
            $this->hasJournalLink()
                ? 'row_journal.journal_id as journal_no'
                : DB::raw('NULL as journal_no'),
        ];
    }

    /**
     * IS2034 #3: can a ledger row be traced back to a journal on this tenant?
     *
     * account_transactions.journal_entry is optional - JournalController writes it
     * only when the column exists - and `journals` is a core table this module ships
     * no migration for (see Database/SQL/IS1992_Journals_Add_Ledger_Columns.sql).
     * Both are checked so the Account Book keeps working on a database that has
     * neither.
     */
    private function hasJournalLink(): bool
    {
        return $this->hasColumn('account_transactions', 'journal_entry')
            && $this->hasColumn('journals', 'journal_id');
    }

    private function hasColumn(string $table, string $column): bool
    {
        $connection = Schema::getConnection()->getName();
        $database = (string) Schema::getConnection()->getDatabaseName();
        $key = $connection . '|' . $database . '|' . $table . '|' . $column;

        if (! array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$key];
    }

    private function formatRow(
        object $row,
        ?object $transaction,
        ?object $payment,
        ?object $contact,
        float $openingBalance,
        float $balance,
        int $precision,
        array $hiddenDescriptionLines
    ): array {
        $amount = (float) ($row->amount ?? 0);
        $debit = $row->type === 'debit' ? $amount : 0.0;
        $credit = $row->type === 'credit' ? $amount : 0.0;
        $chequeNumber = $payment->cheque_number ?? $row->cheque_number ?? '';
        $chequeDate = $payment->cheque_date ?? $row->cheque_date ?? null;
        $displayNote = $this->userEnteredNote(
            $row,
            $transaction,
            $payment,
            $hiddenDescriptionLines
        );

        $transactionDate = $row->operation_date;
        if ($this->isSupplierPaymentRow($row, $transaction, $payment) && ! empty($payment->paid_on)) {
            // S763: Finance > Account Book > Transaction Date must show the
            // date selected in Supplier Payment. This applies to BOTH the
            // selected Payment Account and Accounts Payable ledger rows.
            $transactionDate = $payment->paid_on;
        } elseif (($row->sub_type ?? null) !== 'deposit' && ! empty($transaction->transaction_date)) {
            $transactionDate = $transaction->transaction_date;
        }

        return [
            'DT_RowId' => 'account_transaction_' . (int) $row->id,
            // Keep an empty action key for an older cached view, while the
            // current Account Book has no separate Action column.
            'action' => '',
            'note_button' => $this->noteButtonHtml($displayNote),
            // First Account Book column = system entry Date + Time. Keep the
            // date and time as separate values because the Blade renderer places
            // the time beneath the date. created_at is the system-entered
            // timestamp; operation_date is only a safe fallback for historical
            // rows that pre-date created_at.
            'operation_date' => $this->formatEntryDate($row->created_at ?: $row->operation_date),
            'operation_date_raw' => (string) ($row->created_at ?: $row->operation_date ?: ''),
            'operation_time' => $this->formatEntryTime($row->created_at ?: $row->operation_date),
            'realize_date' => $this->formatDate($transactionDate),
            'description' => $this->descriptionHtml(
                $row,
                $transaction,
                $payment,
                $contact,
                $displayNote
            ),
            'slip_no' => e((string) ($row->slip_no ?: ($payment->payment_ref_no ?? '-'))),
            'cheque_number' => e((string) $chequeNumber),
            'cheque_date' => $this->formatDate($chequeDate),
            'opening_balance' => $this->moneyHtml($openingBalance, 'opening_balance_col', $precision, true),
            'debit' => $debit > 0
                ? $this->moneyHtml($debit, 'debit_col', $precision, false)
                : '',
            'credit' => $credit > 0
                ? $this->moneyHtml($credit, 'credit_col', $precision, false)
                : '',
            'balance' => $this->moneyHtml($balance, 'balance_col', $precision, true),
            // Keep the actual state in a dedicated metadata key while leaving
            // reconcile_status blank for stale DataTables layouts that still
            // request the removed Reconcile Status column.
            'account_transaction_id' => (int) $row->id,
            'reconcile_state' => (int) ((bool) ($row->reconcile_status ?? 0)),
            'reconcile_status' => '',
            'created_at' => (string) ($row->created_at ?: $row->operation_date ?: ''),
            'is_deleted_expense' => ! empty($row->new_deleted_at) ? 1 : 0,
            'new_deleted_at' => (string) ($row->new_deleted_at ?? ''),
        ];
    }

    private function descriptionHtml(
        object $row,
        ?object $transaction,
        ?object $payment,
        ?object $contact,
        string $displayNote
    ): string {
        $parts = [];

        // S763: Supplier Pay Due creates a root transaction_payment without a
        // parent transaction.  The old generic description therefore had no
        // useful transaction type on the Accounts Payable row.  Identify that
        // exact payment shape and use the wording requested by the Supplier
        // workflow.  Advance Payment retains its existing label.
        if ($this->isSupplierPayDueRow($row, $transaction, $payment)) {
            $parts[] = '<strong>Pay Due Amount</strong>';
        } else {
            $type = $transaction->sub_type ?? $transaction->type ?? $row->sub_type ?? null;
            if ($type) {
                $parts[] = '<strong>' . e(ucwords(str_replace('_', ' ', (string) $type))) . '</strong>';
            }
        }

        /*
         * IS2034 #3: label the reference instead of printing it bare.
         *
         * The number was already being shown, but as an unlabelled line among the
         * type, the contact and the note - so nothing on screen said what it was.
         * The ticket asks for it in the form "Transaction No: INV-000125".
         */
        /*
         * S-666 #3: the ticket specifies the exact wording and layout:
         *
         *     Transaction No: INV-000125 | Note: Fuel sales payment
         *
         * One line, both labelled, separated by a pipe. IS2034 put the number and
         * the note on separate lines with no label on the note, which is not what
         * was asked for.
         *
         * Either half can be missing - a journal with no source document, or a
         * transaction with no note - so the pipe is only inserted when there is
         * something on both sides of it, and a row with neither still shows "-".
         */
        $summary = [];

        $reference = $this->transactionNumber($row, $transaction, $payment);
        if ($reference !== '') {
            $summary[] = e(__('account.transaction_no')) . ': ' . e($reference);
        }

        if ($displayNote !== '') {
            $summary[] = e(__('account.note')) . ': ' . nl2br(e($displayNote));
        }

        if ($summary) {
            $parts[] = implode(' | ', $summary);
        }

        if (! empty($contact->name)) {
            $parts[] = e((string) $contact->name);
        }

        return $parts ? implode('<br>', $parts) : '-';
    }

    /**
     * IS2034 #3: the number that identifies the source document for a ledger row.
     *
     * Ordered most specific first. invoice_no and ref_no come from the parent
     * transaction (sales, purchases, expenses); payment_ref_no identifies a
     * standalone payment.
     *
     * slip_no is deliberately NOT used. It has its own column in the Account Book
     * and it identifies a paying-in slip, not the transaction - labelling it
     * "Transaction No" would put a wrong answer on screen rather than none.
     *
     * Journal rows reach the Account Book through account_transactions.journal_entry
     * with no parent transaction at all, so none of the above is set for them - which
     * is why the row in the ticket screenshot showed a description containing only
     * the note. Those fall back to the journal number.
     */
    private function isSupplierPaymentRow(object $row, ?object $transaction, ?object $payment): bool
    {
        if (empty($row->transaction_payment_id) || ! $payment) {
            return false;
        }

        $paymentForType = strtolower(trim((string) ($payment->payment_for_type ?? '')));
        if (in_array($paymentForType, ['supplier', 'both'], true)) {
            return true;
        }

        // Direct purchase/advance payments can be linked through the parent
        // transaction rather than payment_for.  The transaction type is enough
        // to keep Supplier payment dates correct without touching sales/customer
        // payments that share the same Account Book code.
        $transactionType = strtolower(trim((string) ($transaction->type ?? '')));

        return in_array($transactionType, ['purchase', 'purchase_return', 'advance_payment'], true);
    }

    private function isSupplierPayDueRow(object $row, ?object $transaction, ?object $payment): bool
    {
        if (! $this->isSupplierPaymentRow($row, $transaction, $payment) || ! $payment) {
            return false;
        }

        // S766: SLP is the durable Supplier Pay Due reference. Some historical
        // rows were written with a transaction_id even though they belong to
        // the same Pay Due root, so relying only on NULL transaction_id made
        // the description fall back to Purchase/Payment. Never classify an
        // allocation child as its own Pay Due payment.
        $reference = strtoupper(trim((string) ($payment->payment_ref_no ?? '')));
        $isRoot = empty($payment->parent_id);
        if ($isRoot && str_starts_with($reference, 'SLP')) {
            return true;
        }

        // Compatibility for older Supplier Pay Due roots created before SLP
        // references became mandatory.
        return $isRoot && empty($payment->transaction_id) && empty($row->transaction_id);
    }

    private function transactionNumber(object $row, ?object $transaction, ?object $payment): string
    {
        $candidates = [
            $transaction->invoice_no ?? null,
            $transaction->ref_no ?? null,
            $payment->payment_ref_no ?? null,
            $row->payment_ref_no ?? null,
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) ($candidate ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $journalNumber = trim((string) ($row->journal_no ?? ''));
        if ($journalNumber !== '') {
            return 'JOUR' . str_pad($journalNumber, 4, '0', STR_PAD_LEFT);
        }

        return '';
    }

    /**
     * Labels appended by settlement integrations are useful internally but
     * must not be repeated in the Account Book description or Note modal.
     * Resolve them once per request so no per-row database queries are added.
     *
     * @return array<int, string>
     */
    private function accountBookHiddenDescriptionLines(int $businessId, ?int $locationId): array
    {
        $businessName = '';
        $locationName = '';

        try {
            $businessName = trim((string) DB::table('business')
                ->where('id', $businessId)
                ->value('name'));
        } catch (\Throwable $e) {
            $businessName = '';
        }

        if ($locationId) {
            try {
                $locationName = trim((string) DB::table('business_locations')
                    ->where('id', $locationId)
                    ->value('name'));
            } catch (\Throwable $e) {
                $locationName = '';
            }
        }

        $lines = array_filter([
            $businessName,
            $locationName,
            trim($businessName . ' ' . $locationName),
            trim($businessName . ' - ' . $locationName),
            trim($businessName . ' | ' . $locationName),
            trim($businessName . ' / ' . $locationName),
        ], static fn ($line): bool => is_string($line) && trim($line) !== '');

        return array_values(array_unique($lines));
    }

    /**
     * Return only a note that was actually entered by a user.
     *
     * Payment forms (sales, purchases, expenses and transfers) save the user
     * note on transaction_payments.note. Direct Account Deposit entries save
     * it on account_transactions.note. Generated ledger descriptions must not
     * create a Note button by themselves.
     */
    private function userEnteredNote(
        object $row,
        ?object $transaction,
        ?object $payment,
        array $hiddenDescriptionLines
    ): string {
        $paymentNote = trim((string) ($payment->note ?? ''));
        if ($paymentNote !== '') {
            return $this->accountBookDisplayNote($paymentNote, $hiddenDescriptionLines);
        }

        $accountNote = trim((string) ($row->note ?? ''));
        if ($accountNote === '') {
            return '';
        }

        $hasTransaction = ! empty($row->transaction_id);
        $hasPayment = ! empty($row->transaction_payment_id);
        $subType = strtolower(trim((string) ($row->sub_type ?? '')));

        // Manual Account Deposits and other direct account entries do not have
        // a parent transaction/payment row. In those entries at.note is the
        // note typed in the account form.
        if (! $hasTransaction && ! $hasPayment) {
            return $this->accountBookDisplayNote($accountNote, $hiddenDescriptionLines);
        }

        // Older Fund Transfer records can have the user note only on the two
        // account transaction rows even though a payment row exists.
        if (! $hasTransaction && $subType === 'fund_transfer') {
            return $this->accountBookDisplayNote($accountNote, $hiddenDescriptionLines);
        }

        // Settlement integrations build a technical account note and may append
        // a genuine user note to it. Remove the generated settlement wording and
        // return only the remaining user-entered part.
        if ($this->isSettlementLedgerRow($row, $transaction, $accountNote)) {
            return $this->settlementUserNote(
                $accountNote,
                $row,
                $transaction,
                $payment,
                $hiddenDescriptionLines
            );
        }

        // For every other payment-linked/system-generated row, an empty
        // transaction_payments.note means that the user did not enter a note.
        return '';
    }

    private function isSettlementLedgerRow(
        object $row,
        ?object $transaction,
        string $accountNote
    ): bool {
        $values = [
            (string) ($transaction->type ?? ''),
            (string) ($transaction->sub_type ?? ''),
            (string) ($row->sub_type ?? ''),
        ];

        foreach ($values as $value) {
            if (stripos($value, 'settlement') !== false) {
                return true;
            }
        }

        return preg_match('/\bsettlement\s*(?:no|number)?\s*[:#-]/i', $accountNote) === 1
            || preg_match('/\[(?=[^\]]*SETL\s*:)[^\]]*\]/i', $accountNote) === 1;
    }

    /**
     * Extract only the optional free-form note appended to a generated
     * settlement ledger description.
     */
    private function settlementUserNote(
        string $note,
        object $row,
        ?object $transaction,
        ?object $payment,
        array $hiddenDescriptionLines
    ): string {
        $cleaned = $this->accountBookDisplayNote($note, $hiddenDescriptionLines);
        if ($cleaned === '') {
            return '';
        }

        $reference = trim((string) (
            $transaction->invoice_no
            ?? $transaction->ref_no
            ?? $payment->payment_ref_no
            ?? ''
        ));
        $type = trim((string) (
            $transaction->sub_type
            ?? $transaction->type
            ?? $row->sub_type
            ?? ''
        ));

        $generatedSegments = [
            'settlement',
            'daily collection',
            'cash payment',
            'card payment',
            'credit payment',
            'cheque payment',
            'bank transfer',
            'other payment',
            str_replace('_', ' ', $type),
            $reference,
            $reference !== '' ? 'settlement no ' . $reference : '',
            $reference !== '' ? 'settlement number ' . $reference : '',
            $reference !== '' ? 'payment ref no ' . $reference : '',
        ];

        $generated = [];
        foreach (array_merge($generatedSegments, $hiddenDescriptionLines) as $segment) {
            $normalized = $this->normalizeDescriptionLine((string) $segment);
            if ($normalized !== '') {
                $generated[$normalized] = true;
            }
        }

        $userSegments = [];
        $segments = preg_split('/\s*\|\s*|\R+/u', $cleaned) ?: [];
        foreach ($segments as $segment) {
            $segment = trim((string) $segment, " \t\n\r\0\x0B|,;-–—");
            if ($segment === '') {
                continue;
            }

            $normalized = $this->normalizeDescriptionLine($segment);
            if ($normalized === '' || isset($generated[$normalized])) {
                continue;
            }

            if (preg_match(
                '/^(?:settlement|invoice|bill|payment(?:\s+ref(?:erence)?)?)\s*(?:no|number)?\s*[:#-]?\s*[A-Z0-9._\/-]+$/iu',
                $segment
            ) === 1) {
                continue;
            }

            if (preg_match('/^(?:cash|card|credit|cheque|bank|other)\s+payment$/iu', $segment) === 1) {
                continue;
            }

            $userSegments[] = $segment;
        }

        return trim(implode("\n", array_values(array_unique($userSegments))));
    }

    /**
     * Keep the user-facing settlement/payment wording while removing the
     * generated technical settlement key, "Daily Collection" label and the
     * repeated business/location footer from Account Book output only.
     */
    private function accountBookDisplayNote(string $note, array $hiddenDescriptionLines): string
    {
        $note = trim(str_replace(["\r\n", "\r"], "\n", $note));
        if ($note === '') {
            return '';
        }

        $hidden = [];
        foreach ($hiddenDescriptionLines as $hiddenLine) {
            $normalized = $this->normalizeDescriptionLine((string) $hiddenLine);
            if ($normalized !== '') {
                $hidden[$normalized] = true;
            }
        }

        $cleanLines = [];
        foreach (explode("\n", $note) as $line) {
            // Remove internal keys such as:
            // [SETL:PDST39|BIZ:2|ACCT:46|CP:62]
            $line = (string) preg_replace(
                '/\[(?=[^\]]*(?:SETL|BIZ|ACCT|CP)\s*:)[^\]]*\]/i',
                '',
                $line
            );

            // This label belongs to the generated technical footer, not the
            // ledger description requested by users.
            $line = (string) preg_replace(
                '/(?:^|\|)\s*Daily\s+Collection\s*(?=\||$)/i',
                '',
                $line
            );

            $line = trim($line, " \t\n\r\0\x0B|,;-–—");
            if ($line === '') {
                continue;
            }

            // Petro PD cash-reconciliation corrections are internal audit
            // markers generated by the settlement finalization process. Keep
            // the underlying account transaction in the ledger, but never
            // present the marker as a user note or normal description.
            if ($this->isSystemGeneratedAuditMarker($line)) {
                continue;
            }

            $normalized = $this->normalizeDescriptionLine($line);
            if ($normalized !== '' && isset($hidden[$normalized])) {
                continue;
            }

            $cleanLines[] = $line;
        }

        return trim(implode("\n", $cleanLines));
    }

    /**
     * Identify technical reconciliation/audit text written by system code.
     * The database value remains untouched for audit purposes; this method
     * only prevents it from being exposed as a user-entered note or regular
     * Account Book description.
     */
    private function isSystemGeneratedAuditMarker(string $value): bool
    {
        $value = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($value === '') {
            return false;
        }

        $patterns = [
            // Current Petro PD settlement cash-save correction marker.
            '/\bPETROPD[\s_-]*CASHSAVE[\s_-]*ROOTFIX[\s_-]*\d+\b/iu',

            // Human-readable portion stored with the correction entry.
            '/\bauto\s+cash\s+correction\b.*\bmatched\s+submitted\s+payment\s+to\s+finali[sz]e\s+total\s+amount\b/iu',

            // Defensive match for the same internal message without its code.
            '/\bmatched\s+submitted\s+payment\s+to\s+finali[sz]e\s+total\s+amount\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDescriptionLine(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $value);
    }

    private function noteButtonHtml(string $displayNote): string
    {
        if ($displayNote === '') {
            return '';
        }

        return '<button type="button" class="btn btn-xs note_btn" style="background:#8F3A84;color:#fff" data-string="'
            . e($displayNote)
            . '">Note</button>';
    }

    private function moneyHtml(float $amount, string $class, int $precision, bool $withSymbol): string
    {
        $number = number_format($amount, $precision, '.', '');

        return '<span class="display_currency ' . $class . '" data-currency_symbol="'
            . ($withSymbol ? 'true' : 'false')
            . '" data-orig-value="' . $number . '">' . $number . '</span>';
    }

    private function currencyPrecision(int $businessId): int
    {
        $sessionPrecision = session('business.currency_precision');
        if (is_numeric($sessionPrecision)) {
            return max(0, min(6, (int) $sessionPrecision));
        }

        try {
            $precision = DB::table('business')->where('id', $businessId)->value('currency_precision');
            return is_numeric($precision) ? max(0, min(6, (int) $precision)) : 2;
        } catch (\Throwable $e) {
            return 2;
        }
    }

    private function netBalance(float $debit, float $credit, bool $normalDebitBalance): float
    {
        return $normalDebitBalance ? ($debit - $credit) : ($credit - $debit);
    }

    private function isDebitNormal(string $accountType): bool
    {
        return stripos($accountType, 'asset') !== false
            || stripos($accountType, 'expense') !== false;
    }

    private function signedAmount(string $type, float $amount, bool $normalDebitBalance): float
    {
        if ($normalDebitBalance) {
            return $type === 'credit' ? -$amount : $amount;
        }

        return $type === 'debit' ? -$amount : $amount;
    }

    private function dateInput($value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatDate($value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function formatEntryDate($value): string
    {
        if (! $value) {
            return '';
        }

        try {
            // S766: Finance Account Book Date and Transaction Date use
            // one unambiguous format across Supplier and non-Supplier rows.
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function formatEntryTime($value): string
    {
        if (! $value) {
            return '';
        }

        try {
            $timeFormat = (int) session('business.time_format', 24) === 24 ? 'H:i' : 'h:i A';

            return Carbon::parse($value)->format($timeFormat);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
