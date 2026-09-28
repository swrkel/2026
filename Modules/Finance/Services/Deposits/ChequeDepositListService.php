<?php

namespace Modules\Finance\Services\Deposits;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finance-owned outstanding customer-cheque reader.
 *
 * Customer Register payments normally link the Cheques in Hand debit directly
 * to a transaction_payments row. Bulk payments can link that debit to a parent
 * payment while the entered cheque fields remain on a child payment. This
 * service resolves both shapes and returns one correctly aligned table row per
 * Cheques in Hand debit without calling a Customers or Purchase controller.
 */
class ChequeDepositListService
{
    public const MAX_ROWS = 500;

    /** @var array<string, bool> */
    private array $columnCache = [];

    /** @return array{rows: Collection, is_truncated: bool} */
    public function search(int $businessId, array $filters = []): array
    {
        if ($businessId <= 0) {
            return ['rows' => collect(), 'is_truncated' => false];
        }

        $paymentType = strtolower(trim((string) ($filters['payment_type'] ?? 'cheque')));
        $selectedChequeNumbers = collect($filters['selected_cheque_numbers'] ?? [])
            ->map(static fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $accountIds = $paymentType === 'pre_payments'
            ? array_filter([$this->findPrepaymentAccountId($businessId)])
            : $this->chequesInHandAccountIds($businessId);

        if ($accountIds === []) {
            return ['rows' => collect(), 'is_truncated' => false];
        }

        $query = $this->baseQuery(
            $businessId,
            $accountIds,
            $paymentType,
            $selectedChequeNumbers
        );

        /*
         * IS2124 - filter on the EFFECTIVE cheque date, not the raw one.
         *
         * The raw expression is NULL for any cheque saved through a screen
         * with no cheque-date field of its own. In SQL, DATE(NULL) BETWEEN x
         * AND y evaluates to NULL, which is not TRUE, so those rows were
         * dropped by the date filter no matter which range was chosen - they
         * could not be found at all.
         *
         * LA-1176 added the paid_on / operation_date fallback but applied it
         * only to the displayed column, deliberately leaving the filter on the
         * raw date so as not to change which rows appeared. That reasoning had
         * it backwards: the rows were already being wrongly excluded, and a
         * row is now matched on exactly the date the user sees in its Cheque
         * Date cell, which is the only way the range filter can make sense to
         * them.
         */
        $this->applyDateFilter(
            $query,
            $this->effectiveChequeDateExpression(),
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        /*
         * IS2124 - same NULL trap on Created On. A legacy or imported
         * account_transactions row with a NULL created_at would vanish from
         * every range, so it falls back to the operation date.
         */
        // IS2189 #1: a saved cheque can live on a direct/parent/child payment
        // that was created after the original Cheques-in-Hand account row (bulk
        // payments and edited payment allocations are the common cases). Filtering
        // Created On only by account_transaction.created_at therefore hid a cheque
        // immediately after it was saved. Match the selected Created On range when
        // ANY linked payment row, or the account row itself, was created in it.
        $this->applyCreatedOnFilter(
            $query,
            $filters['start_date_created'] ?? null,
            $filters['end_date_created'] ?? null
        );

        $contactId = (int) ($filters['contact_id'] ?? 0);
        if ($contactId > 0) {
            $query->where(function ($contactQuery) use ($contactId): void {
                $contactQuery
                    ->where('transaction_contact.id', $contactId)
                    ->orWhere('direct_payment_contact.id', $contactId);

                if ($this->hasParentPayments()) {
                    $contactQuery
                        ->orWhere('parent_payment_contact.id', $contactId)
                        ->orWhere('child_payment_contact.id', $contactId);
                }
            });
        }

        $chequeNo = trim((string) ($filters['cheque_no'] ?? ''));
        if ($chequeNo !== '') {
            $query->whereRaw($this->chequeNumberExpression() . ' = ?', [$chequeNo]);
        }

        $amount = $this->normaliseAmount($filters['amount'] ?? null);
        if ($amount !== null) {
            $query->whereBetween('account_transaction.amount', [
                $amount - 0.0001,
                $amount + 0.0001,
            ]);
        }

        $rows = $query
            ->select([
                DB::raw($this->customerNameExpression() . ' as customer_name'),
                DB::raw($this->chequeNumberExpression() . ' as cheque_number'),
                DB::raw($this->effectiveChequeDateExpression() . ' as cheque_date'),
                DB::raw($this->bankNameExpression() . ' as bank_name'),
                'account_transaction.amount',
                'account_transaction.id',
                'account_transaction.transaction_payment_id',
                'resolved_transaction.id as t_id',
            ])
            // IS2124 - order by the same date the column displays, so the
            // list is not sorted on a value the user cannot see. NULL raw
            // dates previously all sorted together at the top.
            ->orderByRaw($this->effectiveChequeDateExpression() . ' ASC')
            ->orderBy('account_transaction.id')
            ->limit(self::MAX_ROWS + 1)
            ->get();

        $isTruncated = $rows->count() > self::MAX_ROWS;
        if ($isTruncated) {
            $rows = $rows->take(self::MAX_ROWS)->values();
        }

        return ['rows' => $rows, 'is_truncated' => $isTruncated];
    }


    /**
     * Finance-owned values for the Cheque Number / Amount filters.
     *
     * This deliberately reuses the same search path as the table.  The old
     * controller already exposed a Finance filter-options endpoint, but this
     * service did not implement the method, so any caller of that endpoint
     * failed.  Returning both option sets from one call keeps the modal
     * independent from DOM timing and avoids the old cross-module lookup.
     *
     * @return array<string, mixed>|Collection
     */
    public function filterOptions(int $businessId, string $field, array $filters = [])
    {
        $filters['cheque_no'] = null;
        $filters['amount'] = null;
        $filters['selected_cheque_numbers'] = [];

        $rows = $this->search($businessId, $filters)['rows'];

        $chequeNumbers = $rows
            ->pluck('cheque_number')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->sort(static fn ($a, $b) => strnatcasecmp((string) $a, (string) $b))
            ->values();

        $amounts = $rows
            ->pluck('amount')
            ->filter(static fn ($value): bool => $value !== null && is_numeric($value))
            ->map(static fn ($value): string => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.'))
            ->filter()
            ->unique()
            ->sort(static fn ($a, $b) => (float) $a <=> (float) $b)
            ->values();

        $field = strtolower(trim($field));

        if ($field === 'cheque' || $field === 'cheque_no' || $field === 'cheque_number') {
            return $chequeNumbers;
        }

        if ($field === 'amount') {
            return $amounts;
        }

        return [
            'cheque_numbers' => $chequeNumbers->all(),
            'amounts' => $amounts->all(),
        ];
    }

    private function baseQuery(
        int $businessId,
        array $accountIds,
        string $paymentType,
        array $selectedChequeNumbers
    ): Builder {
        $query = DB::table('account_transactions as account_transaction')
            ->join('accounts as source_account', 'source_account.id', '=', 'account_transaction.account_id')
            ->leftJoin(
                'transaction_payments as direct_payment',
                'direct_payment.id',
                '=',
                'account_transaction.transaction_payment_id'
            );

        if ($this->hasParentPayments()) {
            $query->leftJoin(
                'transaction_payments as parent_payment',
                'parent_payment.id',
                '=',
                'direct_payment.parent_id'
            );

            // Select one actual cheque-bearing child row per bulk-payment parent.
            // Selecting the child id first prevents cheque number/date/bank from
            // being mixed from different children by independent MAX() values.
            $childLookup = DB::table('transaction_payments as cheque_child')
                ->selectRaw('cheque_child.parent_id, MAX(cheque_child.id) as child_id')
                ->whereNotNull('cheque_child.parent_id')
                ->where(function ($chequeFields): void {
                    $chequeFields
                        ->whereRaw("LOWER(COALESCE(cheque_child.method, '')) = ?", ['cheque'])
                        ->orWhereNotNull('cheque_child.cheque_number')
                        ->orWhereNotNull('cheque_child.cheque_date')
                        ->orWhereNotNull('cheque_child.bank_name');
                });

            if ($this->hasColumn('transaction_payments', 'deleted_at')) {
                $childLookup->whereNull('cheque_child.deleted_at');
            }

            $childLookup->groupBy('cheque_child.parent_id');

            $query
                ->leftJoinSub($childLookup, 'cheque_child_lookup', function ($join): void {
                    $join->on('cheque_child_lookup.parent_id', '=', 'direct_payment.id')
                        ->orOn('cheque_child_lookup.parent_id', '=', 'parent_payment.id');
                })
                ->leftJoin(
                    'transaction_payments as child_payment',
                    'child_payment.id',
                    '=',
                    'cheque_child_lookup.child_id'
                );
        }

        $transactionIdExpression = $this->transactionIdExpression();
        $query
            ->leftJoin('transactions as resolved_transaction', function ($join) use ($transactionIdExpression): void {
                $join->on('resolved_transaction.id', '=', DB::raw($transactionIdExpression));
            })
            ->leftJoin(
                'contacts as transaction_contact',
                'transaction_contact.id',
                '=',
                'resolved_transaction.contact_id'
            )
            ->leftJoin(
                'contacts as direct_payment_contact',
                'direct_payment_contact.id',
                '=',
                'direct_payment.payment_for'
            );

        if ($this->hasParentPayments()) {
            $query
                ->leftJoin(
                    'contacts as parent_payment_contact',
                    'parent_payment_contact.id',
                    '=',
                    'parent_payment.payment_for'
                )
                ->leftJoin(
                    'contacts as child_payment_contact',
                    'child_payment_contact.id',
                    '=',
                    'child_payment.payment_for'
                );
        }

        $query
            // S738: source_account.business_id + the resolved Cheques-in-Hand
            // account ids are the authoritative business boundary. Do not hide a
            // real ledger cheque because an older/imported account_transactions
            // row has NULL/0/stale business_id metadata.
            ->where('source_account.business_id', $businessId)
            ->whereIn('account_transaction.account_id', $accountIds)
            // A customer cheque enters Cheques in Hand as one debit. Restricting
            // to that debit prevents the later deposit credit from shifting or
            // duplicating the modal rows.
            ->where('account_transaction.type', 'debit')
            ->where('account_transaction.amount', '>', 0);

        if ($this->hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull('account_transaction.deleted_at');
        }

        if ($paymentType === 'pre_payments' && $this->hasColumn('transaction_payments', 'deleted_at')) {
            // Pre-payment mode is payment-record driven. Normal customer cheque
            // mode is ledger driven (S738) and must not disappear merely because
            // an old Customer payment row was soft-deleted or reshaped.
            $query->where(function ($activePayment): void {
                $activePayment
                    ->whereNull('direct_payment.deleted_at')
                    ->orWhereNull('account_transaction.transaction_payment_id');
            });
        }

        if ($paymentType === 'pre_payments') {
            return $query
                ->whereRaw('LOWER(' . $this->paymentMethodExpression() . ') = ?', ['pre_payments'])
                ->where(function ($available) use ($selectedChequeNumbers): void {
                    $available->whereRaw($this->depositedStatusExpression() . ' = 0');
                    if ($selectedChequeNumbers !== []) {
                        $available->orWhereIn(
                            DB::raw($this->chequeNumberExpression()),
                            $selectedChequeNumbers
                        );
                    }
                });
        }

        /*
         * S738 #2 - Cheques in Hand is the single source of truth.
         *
         * Do NOT decide whether a cheque belongs in the deposit popup from
         * transaction_payments.method or transaction_payments.is_deposited.
         * Those fields differ between Customer Pay Due, Customer Advance,
         * Bulk Payment and older imported rows. The accounting ledger already
         * tells us the real position:
         *
         *   - an incoming customer cheque is a DEBIT in a Cheques-in-Hand account;
         *   - it remains available until a matching CREDIT leaves that SAME account.
         *
         * Therefore every cheque-bearing debit in Cheques in Hand is shown,
         * irrespective of which screen created it. Only an actual matching
         * ledger credit removes it from the popup.
         */
        return $query
            // Every positive debit in a Cheques-in-Hand account is a held
            // cheque by definition. Do not require duplicated cheque metadata
            // to exist in transaction_payments before the ledger row can show.
            ->where(function ($available) use ($selectedChequeNumbers): void {
                $available->whereNotExists(function ($consumed): void {
                    $consumed->select(DB::raw('1'))
                        ->from('account_transactions as cheque_consumed')
                        ->whereColumn('cheque_consumed.account_id', 'account_transaction.account_id')
                        ->where('cheque_consumed.type', 'credit')
                        ->whereColumn('cheque_consumed.amount', 'account_transaction.amount');

                    if ($this->hasColumn('account_transactions', 'deleted_at')) {
                        $consumed->whereNull('cheque_consumed.deleted_at');
                    }

                    $consumed->where(function ($sameCheque): void {
                        // Normal/current rows: the payment id is the strongest
                        // identity and survives even when a cheque number was not
                        // copied to the deposit credit row.
                        $sameCheque->where(function ($byPayment): void {
                            $byPayment
                                ->whereRaw('account_transaction.transaction_payment_id IS NOT NULL')
                                ->whereColumn(
                                    'cheque_consumed.transaction_payment_id',
                                    'account_transaction.transaction_payment_id'
                                );
                        });

                        // Legacy/manual rows can have no transaction_payment_id.
                        // For those, use the cheque number stored in the ledger.
                        if ($this->hasColumn('account_transactions', 'cheque_number')) {
                            $sameCheque->orWhere(function ($byChequeNumber): void {
                                $byChequeNumber
                                    ->whereRaw('account_transaction.transaction_payment_id IS NULL')
                                    ->whereRaw($this->chequeNumberExpression() . " <> ''")
                                    ->whereRaw("COALESCE(NULLIF(cheque_consumed.cheque_number, ''), '') <> ''")
                                    ->whereRaw(
                                        "COALESCE(NULLIF(cheque_consumed.cheque_number, ''), '') = "
                                        . $this->chequeNumberExpression()
                                    );
                            });
                        }

                        // Last-resort ledger identity for valid Cheques-in-Hand
                        // rows that have neither a payment link nor duplicated
                        // cheque metadata. Finance links the original held-cheque
                        // debit to its outgoing credit after deposit.
                        if ($this->hasColumn('account_transactions', 'transfer_transaction_id')) {
                            $sameCheque->orWhereColumn(
                                'cheque_consumed.id',
                                'account_transaction.transfer_transaction_id'
                            );
                        }
                    });
                });

                // Preserve a currently selected cheque during a UI refresh.
                if ($selectedChequeNumbers !== []) {
                    $available->orWhereIn(
                        DB::raw($this->chequeNumberExpression()),
                        $selectedChequeNumbers
                    );
                }
            });
    }

    private function transactionIdExpression(): string
    {
        if (! $this->hasParentPayments()) {
            return 'COALESCE(account_transaction.transaction_id, direct_payment.transaction_id)';
        }

        return 'COALESCE(account_transaction.transaction_id, direct_payment.transaction_id, parent_payment.transaction_id, child_payment.transaction_id)';
    }

    private function paymentMethodExpression(): string
    {
        if (! $this->hasParentPayments()) {
            return "COALESCE(NULLIF(direct_payment.method, ''), '')";
        }

        return "COALESCE(NULLIF(direct_payment.method, ''), NULLIF(parent_payment.method, ''), NULLIF(child_payment.method, ''), '')";
    }

    private function depositedStatusExpression(): string
    {
        if (! $this->hasParentPayments()) {
            return 'COALESCE(direct_payment.is_deposited, 0)';
        }

        return 'GREATEST(COALESCE(direct_payment.is_deposited, 0), COALESCE(parent_payment.is_deposited, 0), COALESCE(child_payment.is_deposited, 0))';
    }

    private function chequeNumberExpression(): string
    {
        $ledgerNumber = $this->nullableTextColumn('account_transactions', 'account_transaction', 'cheque_number');
        $directNumber = $this->nullableTextColumn('transaction_payments', 'direct_payment', 'cheque_number');

        if (! $this->hasParentPayments()) {
            return "COALESCE({$ledgerNumber}, {$directNumber}, '')";
        }

        $parentNumber = $this->nullableTextColumn('transaction_payments', 'parent_payment', 'cheque_number');
        $childNumber = $this->nullableTextColumn('transaction_payments', 'child_payment', 'cheque_number');

        return "COALESCE({$ledgerNumber}, {$childNumber}, {$directNumber}, {$parentNumber}, '')";
    }

    private function chequeDateExpression(): string
    {
        $accountDate = $this->nullableDateColumn('account_transactions', 'account_transaction', 'cheque_date');
        $directDate = $this->nullableDateColumn('transaction_payments', 'direct_payment', 'cheque_date');

        if (! $this->hasParentPayments()) {
            return 'COALESCE(' . $accountDate . ', ' . $directDate . ')';
        }

        $parentDate = $this->nullableDateColumn('transaction_payments', 'parent_payment', 'cheque_date');
        $childDate = $this->nullableDateColumn('transaction_payments', 'child_payment', 'cheque_date');

        return 'COALESCE(' . $accountDate . ', ' . $childDate . ', ' . $directDate . ', ' . $parentDate . ')';
    }

    /**
     * The cheque's effective date: what is displayed, filtered and sorted on.
     *
     * IS2124: this was LA-1176's display-only expression, kept deliberately
     * separate from chequeDateExpression() so that widening it could not
     * "silently change WHICH cheques appear in the list".
     *
     * That separation was the defect. A cheque with a NULL raw cheque_date
     * could never satisfy DATE(NULL) BETWEEN ? AND ?, so it was already being
     * excluded from every date range - invisible rather than merely undated.
     * Display, filtering and ordering now all use this one expression, so a
     * row is matched on precisely the date shown in its Cheque Date cell.
     *
     * A cheque recorded through a screen that has no cheque-date field of its
     * own leaves transaction_payments.cheque_date null while cheque_number and
     * bank_name are populated - exactly the row in the ticket screenshot, which
     * showed a number and a bank against an empty date. The payment date is
     * what the rest of this codebase already substitutes in that situation:
     *
     *   CustomerPaymentController::normalizeCustomerPaymentMethodFields()
     *       $payment->cheque_date = date('Y-m-d', strtotime($payment->paid_on));
     *
     *   CreatesExpenses / UpdatesExpenses
     *       $inputs['cheque_date'] = ... ?: $transaction->transaction_date;
     *
     * so the column falls back the same way rather than inventing a rule.
     */
    private function effectiveChequeDateExpression(): string
    {
        $operationDate = $this->nullableDateColumn('account_transactions', 'account_transaction', 'operation_date');
        $directPaidOn = $this->nullableDateColumn('transaction_payments', 'direct_payment', 'paid_on');

        if (! $this->hasParentPayments()) {
            return 'COALESCE(' . $this->chequeDateExpression() . ', ' . $directPaidOn . ', ' . $operationDate . ')';
        }

        $parentPaidOn = $this->nullableDateColumn('transaction_payments', 'parent_payment', 'paid_on');
        $childPaidOn = $this->nullableDateColumn('transaction_payments', 'child_payment', 'paid_on');

        return 'COALESCE('
            . $this->chequeDateExpression() . ', '
            . $childPaidOn . ', '
            . $directPaidOn . ', '
            . $parentPaidOn . ', '
            . $operationDate
            . ')';
    }

    private function bankNameExpression(): string
    {
        $ledgerBank = $this->nullableTextColumn('account_transactions', 'account_transaction', 'bank_name');
        $directBank = $this->nullableTextColumn('transaction_payments', 'direct_payment', 'bank_name');

        if (! $this->hasParentPayments()) {
            return "COALESCE({$ledgerBank}, {$directBank}, '')";
        }

        $parentBank = $this->nullableTextColumn('transaction_payments', 'parent_payment', 'bank_name');
        $childBank = $this->nullableTextColumn('transaction_payments', 'child_payment', 'bank_name');

        return "COALESCE({$ledgerBank}, {$childBank}, {$directBank}, {$parentBank}, '')";
    }

    private function customerNameExpression(): string
    {
        if (! $this->hasParentPayments()) {
            return "COALESCE(NULLIF(transaction_contact.name, ''), NULLIF(direct_payment_contact.name, ''), '')";
        }

        return "COALESCE(NULLIF(transaction_contact.name, ''), NULLIF(direct_payment_contact.name, ''), NULLIF(parent_payment_contact.name, ''), NULLIF(child_payment_contact.name, ''), '')";
    }

    /**
     * SQL-safe nullable text expression for tenant schemas which are not all at
     * the same migration level. Single quoted literals also keep the query valid
     * when a server enables MySQL ANSI_QUOTES mode.
     */
    private function nullableTextColumn(string $table, string $alias, string $column): string
    {
        if (! $this->hasColumn($table, $column)) {
            return 'NULL';
        }

        return "NULLIF(TRIM({$alias}.{$column}), '')";
    }

    private function nullableDateColumn(string $table, string $alias, string $column): string
    {
        if (! $this->hasColumn($table, $column)) {
            return 'NULL';
        }

        return "NULLIF(NULLIF({$alias}.{$column}, '0000-00-00'), '0000-00-00 00:00:00')";
    }

    /**
     * Return every active account that represents customer cheques in hand.
     *
     * Some businesses post cheque receipts directly to the legacy parent
     * account named "Cheques in Hand". Others use an operational child account
     * linked to the account group "Cheques in Hand (Customer's)". Restricting
     * the deposit list to one exact account name hides those valid saved cheques.
     *
     * @return array<int, int>
     */
    public function chequesInHandAccountIds(int $businessId): array
    {
        if ($businessId <= 0 || ! $this->schema()->hasTable('accounts')) {
            return [];
        }

        $baseAccounts = function () use ($businessId): Builder {
            $query = DB::table('accounts')->where('business_id', $businessId);

            if ($this->schema()->hasColumn('accounts', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if ($this->schema()->hasColumn('accounts', 'is_closed')) {
                $query->where(function ($active): void {
                    $active->where('is_closed', 0)->orWhereNull('is_closed');
                });
            }

            return $query;
        };

        $legacyParentIds = $baseAccounts()
            ->where(function ($name): void {
                $name
                    ->whereRaw('LOWER(TRIM(name)) = ?', ['cheques in hand'])
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', ["cheques in hand (customer's)"])
                    ->orWhere(function ($like): void {
                        $like
                            ->whereRaw('LOWER(name) LIKE ?', ['%cheque%'])
                            ->whereRaw('LOWER(name) LIKE ?', ['%hand%']);
                    });
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $groupIds = collect();
        if ($this->schema()->hasTable('account_groups')) {
            $groupQuery = DB::table('account_groups');

            if ($this->schema()->hasColumn('account_groups', 'business_id')) {
                $groupQuery->where('business_id', $businessId);
            }
            if ($this->schema()->hasColumn('account_groups', 'deleted_at')) {
                $groupQuery->whereNull('deleted_at');
            }

            $groupIds = $groupQuery
                ->where(function ($name): void {
                    $name
                        ->whereRaw('LOWER(TRIM(name)) = ?', ["cheques in hand (customer's)"])
                        ->orWhereRaw('LOWER(TRIM(name)) = ?', ['cheques in hand'])
                        ->orWhere(function ($like): void {
                            $like
                                ->whereRaw('LOWER(name) LIKE ?', ['%cheque%'])
                                ->whereRaw('LOWER(name) LIKE ?', ['%hand%']);
                        });
                })
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter()
                ->unique()
                ->values();
        }

        $accountIds = $legacyParentIds->values();

        if ($groupIds->isNotEmpty()) {
            $linkedColumns = array_values(array_filter(
                ['asset_type', 'account_group_id', 'group_id'],
                fn (string $column): bool => $this->schema()->hasColumn('accounts', $column)
            ));

            if ($linkedColumns !== []) {
                $groupAccounts = $baseAccounts()
                    ->where(function ($query) use ($groupIds, $linkedColumns): void {
                        foreach ($linkedColumns as $index => $column) {
                            if ($index === 0) {
                                $query->whereIn($column, $groupIds->all());
                            } else {
                                $query->orWhereIn($column, $groupIds->all());
                            }
                        }
                    })
                    ->pluck('id');

                $accountIds = $accountIds->merge($groupAccounts);
            }
        }

        if ($legacyParentIds->isNotEmpty() && $this->schema()->hasColumn('accounts', 'parent_account_id')) {
            $accountIds = $accountIds->merge(
                $baseAccounts()
                    ->whereIn('parent_account_id', $legacyParentIds->all())
                    ->pluck('id')
            );
        }

        return $accountIds
            ->map(static fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function findPrepaymentAccountId(int $businessId): int
    {
        $query = DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereIn(DB::raw('LOWER(TRIM(name))'), [
                'pre_payments',
                'pre payments',
                'prepayments',
            ]);

        if ($this->hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) ($query->value('id') ?: 0);
    }

    private function applyCreatedOnFilter(
        Builder $query,
        mixed $startValue,
        mixed $endValue
    ): void {
        $start = $this->normaliseDate($startValue);
        $end = $this->normaliseDate($endValue);

        if ($start === null || $end === null) {
            return;
        }

        $query->where(function ($createdOn) use ($start, $end): void {
            $createdOn->whereRaw(
                'DATE(COALESCE(account_transaction.created_at, account_transaction.operation_date)) BETWEEN ? AND ?',
                [$start, $end]
            )->orWhereRaw(
                'DATE(COALESCE(direct_payment.created_at, direct_payment.paid_on)) BETWEEN ? AND ?',
                [$start, $end]
            );

            if ($this->hasParentPayments()) {
                $createdOn
                    ->orWhereRaw(
                        'DATE(COALESCE(parent_payment.created_at, parent_payment.paid_on)) BETWEEN ? AND ?',
                        [$start, $end]
                    )
                    ->orWhereRaw(
                        'DATE(COALESCE(child_payment.created_at, child_payment.paid_on)) BETWEEN ? AND ?',
                        [$start, $end]
                    );
            }
        });
    }

    private function applyDateFilter(
        Builder $query,
        string $columnExpression,
        mixed $startValue,
        mixed $endValue
    ): void {
        $start = $this->normaliseDate($startValue);
        $end = $this->normaliseDate($endValue);

        if ($start === null || $end === null) {
            return;
        }

        $query->whereRaw('DATE(' . $columnExpression . ') BETWEEN ? AND ?', [$start, $end]);
    }

    private function normaliseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normaliseAmount(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalised = str_replace([',', ' '], '', (string) $value);

        return is_numeric($normalised) ? (float) $normalised : null;
    }

    private function hasParentPayments(): bool
    {
        return $this->hasColumn('transaction_payments', 'parent_id');
    }

    /**
     * Always inspect the schema on the CURRENT default DB connection.
     *
     * In this application the default connection is switched per tenant. Using
     * the Schema facade directly can retain metadata from the central/previous
     * connection during a long-lived request, which made parent-payment and
     * account-group branches disappear even though those columns existed in the
     * active tenant database.
     */
    private function schema()
    {
        return DB::connection()->getSchemaBuilder();
    }

    private function hasColumn(string $table, string $column): bool
    {
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();
        $key = $connection->getName() . '|' . $database . '|' . $table . '|' . $column;

        if (! array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = $connection->getSchemaBuilder()->hasColumn($table, $column);
        }

        return $this->columnCache[$key];
    }
}
