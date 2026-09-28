<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Modules\Customers\Support\SchemaCache;
use Modules\Customers\Entities\Customer;

class CustomerLedgerService
{
    protected CustomerReceivableService $receivableService;
    protected CustomerPaymentDuplicateResolver $duplicateResolver;

    public function __construct(
        ?CustomerReceivableService $receivableService = null,
        ?CustomerPaymentDuplicateResolver $duplicateResolver = null
    ) {
        $this->receivableService = $receivableService ?: new CustomerReceivableService();
        $this->duplicateResolver = $duplicateResolver ?: new CustomerPaymentDuplicateResolver();
    }

    public function customerList(int $businessId, int $limit = 500)
    {
        $query = $this->customerBaseQuery($businessId);

        return $query
            ->select($this->customerSelectColumns())
            ->orderBy($this->contactsHasColumn('name') ? 'name' : 'id')
            ->limit($limit)
            ->get();
    }

    public function inactiveCustomers(int $businessId, int $limit = 500)
    {
        $query = $this->customerBaseQuery($businessId);

        if ($this->contactsHasColumn('active')) {
            $query->where('active', 0);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->select($this->customerSelectColumns())
            ->orderBy($this->contactsHasColumn('name') ? 'name' : 'id')
            ->limit($limit)
            ->get();
    }

    public function summary(int $businessId): array
    {
        $customerQuery = $this->customerBaseQuery($businessId);

        $summary = [
            'total_customers' => (clone $customerQuery)->count(),
            'active_customers' => $this->contactsHasColumn('active') ? (clone $customerQuery)->where('active', 1)->count() : (clone $customerQuery)->count(),
            'inactive_customers' => $this->contactsHasColumn('active') ? (clone $customerQuery)->where('active', 0)->count() : 0,
            'credit_limit_total' => $this->contactsHasColumn('credit_limit') ? (float) (clone $customerQuery)->sum('credit_limit') : 0.0,
            'invoice_total' => 0.0,
            'payment_total' => 0.0,
            'outstanding_total' => 0.0,
        ];

        if ($this->canUseTransactions()) {
            $summary['invoice_total'] = (float) DB::table('transactions')
                ->where('business_id', $businessId)
                ->whereIn('type', $this->customerTransactionTypes())
                ->whereNull('deleted_at')
                ->sum('final_total');
        }

        if ($this->canUseTransactionPayments()) {
            $summary['payment_total'] = (float) DB::table('transaction_payments')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->sum('amount');
        }

        // S337: Dashboard Outstanding must match the Customer List Total Due logic.
        // It should be based on customer ledger balance + opening balance, not only
        // transaction total minus payment total, because tenant databases post several
        // customer adjustments directly to contact_ledgers.
        $ledgerOutstanding = $this->outstandingTotalFromCustomerLedger($businessId);
        $summary['outstanding_total'] = $ledgerOutstanding !== null
            ? $ledgerOutstanding
            : max($summary['invoice_total'] - $summary['payment_total'], 0);

        return $summary;
    }

    protected function outstandingTotalFromCustomerLedger(int $businessId): ?float
    {
        if (!SchemaCache::hasTable('contacts')) {
            return null;
        }

        $customers = DB::table('contacts')
            ->where('business_id', $businessId)
            ->where('type', 'customer');

        if ($this->contactsHasColumn('deleted_at')) {
            $customers->whereNull('deleted_at');
        }

        $openingExpr = $this->contactsHasColumn('opening_balance')
            ? 'COALESCE(contacts.opening_balance, 0)'
            : '0';

        if (!SchemaCache::hasTable('contact_ledgers')) {
            return (float) $customers
                ->selectRaw("SUM({$openingExpr}) as opening_total")
                ->value('opening_total');
        }

        $ledger = DB::table('contact_ledgers as cl')
            ->where('cl.business_id', $businessId);

        $hasTransactionJoin = SchemaCache::hasColumn('contact_ledgers', 'transaction_id')
            && SchemaCache::hasTable('transactions');
        if ($hasTransactionJoin) {
            $ledger->leftJoin('transactions as ledger_tx', 'cl.transaction_id', '=', 'ledger_tx.id');
        }

        $hasPaymentJoin = SchemaCache::hasColumn('contact_ledgers', 'transaction_payment_id')
            && SchemaCache::hasTable('transaction_payments');
        if ($hasPaymentJoin) {
            $ledger->leftJoin('transaction_payments as ledger_tp', 'cl.transaction_payment_id', '=', 'ledger_tp.id');
        }

        $this->applySecurityDepositLedgerExclusion(
            $ledger,
            $hasTransactionJoin ? 'ledger_tx' : null,
            $hasPaymentJoin ? 'ledger_tp' : null
        );

        $ledgerTypeExpr = SchemaCache::hasColumn('contact_ledgers', 'type')
            ? 'cl.type'
            : (SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type') ? 'cl.acc_transaction_type' : "'debit'");

        if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
            $ledger->whereNull('cl.deleted_at');
        }

        // IS2276: duplicate physical ledger postings for one real payment must
        // not inflate dashboard/outstanding totals. Apply the same canonical
        // payment-ledger rule used by the Customer Ledger rows themselves.
        $this->duplicateResolver->applyCanonicalLedgerPostingFilter($ledger, 'cl');

        $ledger->selectRaw("cl.contact_id, SUM(CASE WHEN {$ledgerTypeExpr} = 'credit' THEN -cl.amount ELSE cl.amount END) as balance")
            ->groupBy('cl.contact_id');

        return (float) $customers
            ->leftJoinSub($ledger, 'customer_ledger_balance', function ($join) {
                $join->on('contacts.id', '=', 'customer_ledger_balance.contact_id');
            })
            ->selectRaw("SUM({$openingExpr} + COALESCE(customer_ledger_balance.balance, 0)) as outstanding_total")
            ->value('outstanding_total');
    }

    public function ledgerRows(
        int $businessId,
        ?int $customerId = null,
        int $limit = 500,
        bool $includeCustomerColumns = true,
        ?Customer $customer = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $limit = min(max($limit, 1), 5000);
        $rows = collect();

        if ($this->canUseContactLedgers()) {
            $rows = $rows->concat(
                $this->contactLedgerRows(
                    $businessId,
                    $customerId,
                    $limit,
                    $includeCustomerColumns,
                    $startDate,
                    $endDate
                )
            );
        }

        // S526: A saved credit sale can exist in transactions even when the
        // originating module did not post the matching contact_ledgers row.
        // Add only unrepresented transactions/payments to avoid duplicates.
        if (!empty($customerId)) {
            $rows = $rows->concat(
                $this->receivableService->missingRowsForCustomer(
                    $businessId,
                    (int) $customerId,
                    $limit,
                    $includeCustomerColumns,
                    $startDate,
                    $endDate
                )
            );
        } elseif (!$this->canUseContactLedgers() && $this->canUseTransactions()) {
            // Existing report/API behaviour for an all-customer request remains
            // available when a tenant has no contact_ledgers table.
            $query = DB::table('transactions');
            if ($includeCustomerColumns) {
                $query->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id');
            }

            $query->where('transactions.business_id', $businessId)
                ->whereIn('transactions.type', $this->customerTransactionTypes());

            if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
                $query->whereNull('transactions.deleted_at');
            }

            $fallbackDateColumn = SchemaCache::hasColumn('transactions', 'transaction_date')
                ? 'transactions.transaction_date'
                : (SchemaCache::hasColumn('transactions', 'created_at') ? 'transactions.created_at' : null);
            if ($fallbackDateColumn && $startDate) {
                $query->whereDate($fallbackDateColumn, '>=', $startDate);
            }
            if ($fallbackDateColumn && $endDate) {
                $query->whereDate($fallbackDateColumn, '<=', $endDate);
            }

            $rows = $rows->concat($query->select([
                    'transactions.id',
                    'transactions.created_at',
                    'transactions.transaction_date',
                    DB::raw('COALESCE(transactions.invoice_no, transactions.ref_no) as description'),
                    DB::raw("'' as ledger_note"),
                    'transactions.invoice_no',
                    'transactions.ref_no',
                    'transactions.type as transaction_type',
                    'transactions.type',
                    'transactions.payment_status',
                    'transactions.final_total as amount',
                    DB::raw("'debit' as acc_transaction_type"),
                    DB::raw("'' as payment_method"),
                    DB::raw("NULL as transaction_payment_id"),
                    'transactions.id as transaction_id',
                    DB::raw("'' as payment_ref_no"),
                    DB::raw("'' as paid_in_type"),
                    'transactions.contact_id',
                    $includeCustomerColumns ? 'contacts.name as customer_name' : DB::raw("'' as customer_name"),
                    $includeCustomerColumns ? 'contacts.contact_id as customer_code' : DB::raw("'' as customer_code"),
                ])
                ->orderBy('transactions.transaction_date')
                ->orderBy('transactions.id')
                ->limit($limit)
                ->get());
        }

        $rows = $rows->sortBy(function ($row) {
            return sprintf(
                '%s|%020d',
                (string) ($row->transaction_date ?? $row->created_at ?? ''),
                (int) preg_replace('/\D+/', '', (string) ($row->id ?? 0))
            );
        })->values();

        // IS2272: old duplicated saves can have two different references where
        // the retry received a collision suffix (SP2026/0020 and SP2026/0020-1).
        // Suppress only that strict historical duplicate pattern. The original
        // database rows remain untouched for audit/accounting traceability.
        $rows = $this->duplicateResolver->collapse($rows);

        // IS2086: Finance Journals shown in a Customer ledger are ledger
        // adjustments, not generic "Ledger" transactions. Normalise only rows
        // that carry the Finance Journal reference; every other customer ledger
        // row keeps its existing description/type unchanged.
        $rows = $this->normaliseJournalAdjustmentRows($rows);

        // UI-only enrichment for the Customer Ledger table. This attaches
        // payment/transaction reference details in two bulk queries without
        // changing amounts, debit/credit classification, duplicate handling,
        // B/F calculation, or any accounting records.
        $rows = $this->enrichLedgerDisplayDetails($rows, $businessId);

        return $this->withOpeningBalanceRow(
            $rows,
            $businessId,
            $customerId,
            $customer,
            $startDate,
            $endDate
        )->take($limit)->values();
    }


    protected function customerBaseQuery(int $businessId)
    {
        $query = Customer::forBusiness($businessId)->customersOnly();

        if ($this->contactsHasColumn('deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    protected function customerSelectColumns(): array
    {
        $columns = ['id'];

        foreach (['contact_id', 'name', 'mobile', 'email', 'credit_limit', 'active', 'created_at'] as $column) {
            if ($this->contactsHasColumn($column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    protected function contactsHasColumn(string $column): bool
    {
        return in_array($column, SchemaCache::columns('contacts'), true);
    }

    protected function canUseContactLedgers(): bool
    {
        return SchemaCache::hasTable('contact_ledgers');
    }

    protected function contactLedgerRows(
        int $businessId,
        ?int $customerId = null,
        int $limit = 500,
        bool $includeCustomerColumns = true,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $ledgerTable = 'contact_ledgers';
        $has = function (string $column) use ($ledgerTable) {
            return SchemaCache::hasColumn($ledgerTable, $column);
        };

        $query = DB::table($ledgerTable);

        if ($includeCustomerColumns) {
            $query->leftJoin('contacts', $ledgerTable . '.contact_id', '=', 'contacts.id');
        }

        if ($has('transaction_id') && SchemaCache::hasTable('transactions')) {
            $query->leftJoin('transactions', $ledgerTable . '.transaction_id', '=', 'transactions.id');
        }

        if ($has('transaction_payment_id') && SchemaCache::hasTable('transaction_payments')) {
            $query->leftJoin('transaction_payments', $ledgerTable . '.transaction_payment_id', '=', 'transaction_payments.id');
        }

        $query->where($ledgerTable . '.business_id', $businessId);

        if ($has('deleted_at')) {
            $query->whereNull($ledgerTable . '.deleted_at');
        }

        if (!empty($customerId)) {
            $query->where($ledgerTable . '.contact_id', $customerId);
        }

        // Security deposits live on /customers/{id}/security-deposit and must
        // not appear in Customer Ledger or alter its running balance.
        $this->applySecurityDepositLedgerExclusion(
            $query,
            ($has('transaction_id') && SchemaCache::hasTable('transactions')) ? 'transactions' : null,
            ($has('transaction_payment_id') && SchemaCache::hasTable('transaction_payments')) ? 'transaction_payments' : null
        );

        // IS2276: filter duplicate contact_ledgers postings before the LIMIT is
        // applied. This is important on long ledgers: in-memory de-duplication
        // alone could otherwise let duplicates consume the 5,000-row window and
        // hide legitimate older rows.
        $this->duplicateResolver->applyCanonicalLedgerPostingFilter($query, $ledgerTable);

        $hasTransactionsJoin = $has('transaction_id') && SchemaCache::hasTable('transactions');
        $hasPaymentsJoin = $has('transaction_payment_id') && SchemaCache::hasTable('transaction_payments');

        // The ledger period must be filtered by the real BUSINESS transaction
        // date, not by the date/time the mirror row happened to be written into
        // contact_ledgers. Old imports/backfills frequently have operation_date
        // months or years later than the underlying transaction, which made rows
        // disappear from the date range the user selected.
        //
        // Priority:
        //   payment row   -> transaction_payments.paid_on
        //   transaction   -> transactions.transaction_date
        //   ledger-only   -> contact_ledgers.operation_date
        //   last fallback -> contact_ledgers.created_at
        $dateCandidates = [];
        if ($hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'paid_on')) {
            $dateCandidates[] = 'transaction_payments.paid_on';
        }
        if ($hasTransactionsJoin && SchemaCache::hasColumn('transactions', 'transaction_date')) {
            $dateCandidates[] = 'transactions.transaction_date';
        }
        if ($has('operation_date')) {
            $dateCandidates[] = "{$ledgerTable}.operation_date";
        }
        if ($has('created_at')) {
            $dateCandidates[] = "{$ledgerTable}.created_at";
        }
        if (empty($dateCandidates)) {
            $dateCandidates[] = 'NULL';
        }

        $transactionDateExpr = count($dateCandidates) === 1
            ? $dateCandidates[0]
            : 'COALESCE(' . implode(', ', $dateCandidates) . ')';

        if ($startDate) {
            $query->whereRaw('DATE(' . $transactionDateExpr . ') >= ?', [$startDate]);
        }
        if ($endDate) {
            $query->whereRaw('DATE(' . $transactionDateExpr . ') <= ?', [$endDate]);
        }

        $descriptionCandidates = [];
        if ($has('note')) {
            $descriptionCandidates[] = "NULLIF({$ledgerTable}.note, '')";
        }
        if ($has('description')) {
            $descriptionCandidates[] = "NULLIF({$ledgerTable}.description, '')";
        }
        if ($hasTransactionsJoin) {
            $descriptionCandidates[] = "NULLIF(transactions.invoice_no, '')";
            $descriptionCandidates[] = "NULLIF(transactions.ref_no, '')";
        }
        if ($hasPaymentsJoin) {
            $descriptionCandidates[] = "NULLIF(transaction_payments.payment_ref_no, '')";
        }
        $descriptionCandidates[] = "'Ledger'";
        $descriptionExpr = 'COALESCE(' . implode(', ', $descriptionCandidates) . ')';

        $ledgerTypeExpr = $has('type')
            ? "{$ledgerTable}.type"
            : ($has('acc_transaction_type') ? "{$ledgerTable}.acc_transaction_type" : "'debit'");

        $amountExpr = $has('amount') ? "{$ledgerTable}.amount" : '0';
        $invoiceExpr = $hasTransactionsJoin ? "COALESCE(transactions.invoice_no, '')" : "''";
        $referenceExpr = $hasTransactionsJoin ? "COALESCE(transactions.ref_no, '')" : "''";
        $transactionTypeExpr = $hasTransactionsJoin
            ? "COALESCE(transactions.type, {$ledgerTypeExpr})"
            : $ledgerTypeExpr;
        if ($hasTransactionsJoin && $hasPaymentsJoin) {
            $paymentStatusExpr = "COALESCE(NULLIF(transactions.payment_status, ''), CASE WHEN transaction_payments.id IS NOT NULL THEN 'paid' ELSE '' END)";
        } elseif ($hasTransactionsJoin) {
            $paymentStatusExpr = "COALESCE(transactions.payment_status, '')";
        } elseif ($hasPaymentsJoin) {
            $paymentStatusExpr = "CASE WHEN transaction_payments.id IS NOT NULL THEN 'paid' ELSE '' END";
        } else {
            $paymentStatusExpr = "''";
        }
        $paymentMethodExpr = $hasPaymentsJoin ? "COALESCE(transaction_payments.method, '')" : "''";
        $paymentReferenceExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'payment_ref_no')
            ? "COALESCE(transaction_payments.payment_ref_no, '')"
            : "''";
        $paidInTypeExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'paid_in_type')
            ? "COALESCE(transaction_payments.paid_in_type, '')"
            : "''";
        $chequeNumberExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'cheque_number')
            ? "COALESCE(transaction_payments.cheque_number, '')"
            : "''";
        $bankNameExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'bank_name')
            ? "COALESCE(transaction_payments.bank_name, '')"
            : "''";
        $chequeDateExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'cheque_date')
            ? "transaction_payments.cheque_date"
            : 'NULL';
        $transferDateExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'transfer_date')
            ? "transaction_payments.transfer_date"
            : 'NULL';
        $paymentVoucherExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'transaction_no')
            ? "COALESCE(transaction_payments.transaction_no, '')"
            : ($hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'reference_no')
                ? "COALESCE(transaction_payments.reference_no, '')"
                : "''");
        $paymentReferenceNoExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'reference_no')
            ? "COALESCE(transaction_payments.reference_no, '')"
            : "''";
        $cardTransactionExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'card_transaction_number')
            ? "COALESCE(transaction_payments.card_transaction_number, '')"
            : "''";
        $cardNumberExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'card_number')
            ? "COALESCE(transaction_payments.card_number, '')"
            : "''";
        $cardTypeExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'card_type')
            ? "COALESCE(transaction_payments.card_type, '')"
            : "''";
        $cardHolderExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'card_holder_name')
            ? "COALESCE(transaction_payments.card_holder_name, '')"
            : "''";
        $paymentNoteExpr = $hasPaymentsJoin && SchemaCache::hasColumn('transaction_payments', 'note')
            ? "COALESCE(transaction_payments.note, '')"
            : "''";
        $customerReferenceExpr = $hasTransactionsJoin && SchemaCache::hasColumn('transactions', 'customer_ref')
            ? "COALESCE(transactions.customer_ref, '')"
            : "''";
        $saleReferenceExpr = $hasTransactionsJoin && SchemaCache::hasColumn('transactions', 'sale_ref')
            ? "COALESCE(transactions.sale_ref, '')"
            : "''";
        $orderNumberExpr = $hasTransactionsJoin && SchemaCache::hasColumn('transactions', 'order_no')
            ? "COALESCE(transactions.order_no, '')"
            : "''";
        $transactionPaymentIdExpr = $has('transaction_payment_id')
            ? "{$ledgerTable}.transaction_payment_id"
            : 'NULL';
        $transactionIdExpr = $has('transaction_id')
            ? "{$ledgerTable}.transaction_id"
            : ($hasTransactionsJoin ? 'transactions.id' : 'NULL');

        $select = [
            $ledgerTable . '.id',
            $ledgerTable . '.created_at',
            DB::raw($transactionDateExpr . ' as transaction_date'),
            DB::raw($descriptionExpr . ' as description'),
            DB::raw($has('note') ? "COALESCE({$ledgerTable}.note, '') as ledger_note" : "'' as ledger_note"),
            DB::raw($invoiceExpr . ' as invoice_no'),
            DB::raw($referenceExpr . ' as ref_no'),
            DB::raw($transactionTypeExpr . ' as transaction_type'),
            DB::raw($ledgerTypeExpr . ' as type'),
            DB::raw($paymentStatusExpr . ' as payment_status'),
            DB::raw($amountExpr . ' as amount'),
            DB::raw($ledgerTypeExpr . ' as acc_transaction_type'),
            DB::raw($paymentMethodExpr . ' as payment_method'),
            DB::raw($transactionPaymentIdExpr . ' as transaction_payment_id'),
            DB::raw($transactionIdExpr . ' as transaction_id'),
            DB::raw($paymentReferenceExpr . ' as payment_ref_no'),
            DB::raw($paidInTypeExpr . ' as paid_in_type'),
            DB::raw($chequeNumberExpr . ' as cheque_number'),
            DB::raw($bankNameExpr . ' as bank_name'),
            DB::raw($chequeDateExpr . ' as cheque_date'),
            DB::raw($transferDateExpr . ' as transfer_date'),
            DB::raw($paymentVoucherExpr . ' as voucher_no'),
            DB::raw($paymentReferenceNoExpr . ' as payment_reference_no'),
            DB::raw($cardTransactionExpr . ' as card_transaction_number'),
            DB::raw($cardNumberExpr . ' as card_number'),
            DB::raw($cardTypeExpr . ' as card_type'),
            DB::raw($cardHolderExpr . ' as card_holder_name'),
            DB::raw($paymentNoteExpr . ' as payment_note'),
            DB::raw($customerReferenceExpr . ' as customer_ref'),
            DB::raw($saleReferenceExpr . ' as sale_ref'),
            DB::raw($orderNumberExpr . ' as order_no'),
            $ledgerTable . '.contact_id',
            $includeCustomerColumns ? 'contacts.name as customer_name' : DB::raw("'' as customer_name"),
            $includeCustomerColumns ? 'contacts.contact_id as customer_code' : DB::raw("'' as customer_code"),
        ];

        return $query->select($select)
            ->orderBy('transaction_date')
            ->orderBy($ledgerTable . '.id')
            ->limit($limit)
            ->get();
    }

    /**
     * IS2086 - Present Finance Journal rows with the Customer Ledger wording.
     *
     * A Journal transaction is deliberately recognised from its Finance-owned
     * reference (for example "Journal: 2"). We do not classify every generic
     * transaction type="ledger" row as a Journal because other modules also use
     * that type for legitimate customer adjustments.
     */
    /**
     * Attach display-only transaction/payment details to ledger rows.
     *
     * Kept deliberately separate from the accounting queries: no amount,
     * balance, row identity, date or duplicate rule is changed here.
     */
    protected function enrichLedgerDisplayDetails($rows, int $businessId)
    {
        $rows = collect($rows);
        if ($rows->isEmpty()) {
            return $rows;
        }

        $paymentIds = $rows->pluck('transaction_payment_id')
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $transactionIds = $rows->pluck('transaction_id')
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $paymentMap = collect();
        if (!empty($paymentIds) && SchemaCache::hasTable('transaction_payments')) {
            $paymentQuery = DB::table('transaction_payments')->whereIn('id', $paymentIds);
            if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                $paymentQuery->where('business_id', $businessId);
            }

            $select = ['id'];
            $fields = [
                'method', 'payment_ref_no', 'reference_no', 'transaction_no',
                'cheque_number', 'cheque_date', 'bank_name', 'transfer_date',
                'card_transaction_number', 'card_number', 'card_type',
                'card_holder_name', 'note',
            ];
            foreach ($fields as $field) {
                $select[] = SchemaCache::hasColumn('transaction_payments', $field)
                    ? $field
                    : DB::raw("NULL as {$field}");
            }

            $paymentMap = $paymentQuery->select($select)->get()->keyBy('id');
        }

        $transactionMap = collect();
        if (!empty($transactionIds) && SchemaCache::hasTable('transactions')) {
            $transactionQuery = DB::table('transactions')->whereIn('id', $transactionIds);
            if (SchemaCache::hasColumn('transactions', 'business_id')) {
                $transactionQuery->where('business_id', $businessId);
            }

            $select = ['id'];
            foreach (['ref_no', 'customer_ref', 'sale_ref', 'order_no'] as $field) {
                $select[] = SchemaCache::hasColumn('transactions', $field)
                    ? $field
                    : DB::raw("NULL as {$field}");
            }
            $transactionMap = $transactionQuery->select($select)->get()->keyBy('id');
        }

        return $rows->map(function ($row) use ($paymentMap, $transactionMap) {
            $paymentId = (int) ($row->transaction_payment_id ?? 0);
            $transactionId = (int) ($row->transaction_id ?? 0);

            if ($paymentId > 0 && $paymentMap->has($paymentId)) {
                $payment = $paymentMap->get($paymentId);
                $row->payment_method = trim((string) ($row->payment_method ?? '')) !== ''
                    ? $row->payment_method
                    : ($payment->method ?? '');
                $row->payment_ref_no = trim((string) ($row->payment_ref_no ?? '')) !== ''
                    ? $row->payment_ref_no
                    : ($payment->payment_ref_no ?? '');
                $row->payment_reference_no = $payment->reference_no ?? '';
                $row->voucher_no = trim((string) ($payment->transaction_no ?? '')) !== ''
                    ? $payment->transaction_no
                    : ($payment->reference_no ?? '');
                $row->cheque_number = trim((string) ($row->cheque_number ?? '')) !== ''
                    ? $row->cheque_number
                    : ($payment->cheque_number ?? '');
                $row->cheque_date = $payment->cheque_date ?? null;
                $row->bank_name = trim((string) ($row->bank_name ?? '')) !== ''
                    ? $row->bank_name
                    : ($payment->bank_name ?? '');
                $row->transfer_date = $payment->transfer_date ?? null;
                $row->card_transaction_number = $payment->card_transaction_number ?? '';
                $row->card_number = $payment->card_number ?? '';
                $row->card_type = $payment->card_type ?? '';
                $row->card_holder_name = $payment->card_holder_name ?? '';
                $row->payment_note = $payment->note ?? '';
            }

            if ($transactionId > 0 && $transactionMap->has($transactionId)) {
                $transaction = $transactionMap->get($transactionId);
                $row->ref_no = trim((string) ($row->ref_no ?? '')) !== ''
                    ? $row->ref_no
                    : ($transaction->ref_no ?? '');
                $row->customer_ref = $transaction->customer_ref ?? '';
                $row->sale_ref = $transaction->sale_ref ?? '';
                $row->order_no = $transaction->order_no ?? '';
            }

            return $row;
        });
    }

    /**
     * Keep Security Deposit activity completely separate from Customer Ledger.
     * Legacy records are identified by transaction type; current standalone
     * deposits use the CUS-SECURITY-DEPOSIT-* payment reference.
     */
    protected function applySecurityDepositLedgerExclusion($query, ?string $transactionAlias = null, ?string $paymentAlias = null): void
    {
        $excludedTypes = [
            'security_deposit',
            'refund_security_deposit',
            'security_deposit_refund',
        ];

        if ($transactionAlias !== null) {
            $query->where(function ($depositTransaction) use ($transactionAlias, $excludedTypes) {
                $depositTransaction->whereNull($transactionAlias . '.id')
                    ->orWhereNull($transactionAlias . '.type')
                    ->orWhereNotIn($transactionAlias . '.type', $excludedTypes);
            });
        }

        if ($paymentAlias !== null && SchemaCache::hasColumn('transaction_payments', 'payment_ref_no')) {
            $query->where(function ($depositPayment) use ($paymentAlias) {
                $depositPayment->whereNull($paymentAlias . '.payment_ref_no')
                    ->orWhere($paymentAlias . '.payment_ref_no', '')
                    ->orWhere($paymentAlias . '.payment_ref_no', 'not like', 'CUS-SECURITY-DEPOSIT-%');
            });
        }

        if ($paymentAlias !== null && SchemaCache::hasColumn('transaction_payments', 'paid_in_type')) {
            $query->where(function ($depositPaymentType) use ($paymentAlias) {
                $depositPaymentType->whereNull($paymentAlias . '.paid_in_type')
                    ->orWhereNotIn($paymentAlias . '.paid_in_type', ['security_deposit', 'security deposit']);
            });
        }
    }

    protected function normaliseJournalAdjustmentRows($rows)
    {
        return collect($rows)->map(function ($row) {
            $journalId = $this->journalIdFromLedgerRow($row);

            if ($journalId === null) {
                return $row;
            }

            $journalNumber = 'JOUR' . str_pad((string) $journalId, 4, '0', STR_PAD_LEFT);
            $note = trim((string) ($row->ledger_note ?? ''));

            $row->description = 'Ledger Adjustment: ' . $journalNumber
                . PHP_EOL
                . 'Note:' . ($note !== '' ? ' ' . $note : '');
            $row->transaction_type = 'ledger_adjustment';
            $row->is_journal_adjustment = true;
            $row->journal_number = $journalNumber;

            return $row;
        });
    }

    /**
     * Resolve the grouped Finance Journal number from the durable transaction
     * reference. Existing and older records use either "Journal: N" or
     * "Journal N". Already-normalised JOUR000N references are accepted too.
     */
    protected function journalIdFromLedgerRow($row): ?int
    {
        foreach ([
            $row->invoice_no ?? null,
            $row->ref_no ?? null,
            $row->description ?? null,
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            if (preg_match('/^Journal\s*:\s*(\d+)$/i', $candidate, $matches)
                || preg_match('/^Journal\s+(\d+)$/i', $candidate, $matches)
                || preg_match('/^JOUR0*(\d+)$/i', $candidate, $matches)
                || preg_match('/^Ledger\s+Adjustment\s*:\s*JOUR0*(\d+)/i', $candidate, $matches)) {
                $journalId = (int) ($matches[1] ?? 0);
                return $journalId > 0 ? $journalId : null;
            }
        }

        return null;
    }

    /**
     * Return ledger totals without loading and hydrating thousands of rows.
     *
     * The previous implementation called ledgerRows(..., 10000), which repeated
     * the full joined ledger query after the first 500 display rows had already
     * been loaded. This aggregate keeps the exact debit/credit meaning while
     * reducing the totals work to one small SQL result row.
     */
    public function ledgerSummary(int $businessId, int $customerId, ?Customer $customer = null): array
    {
        return $this->receivableService->summaryForCustomer(
            $businessId,
            $customerId,
            $customer
        );
    }


    protected function contactLedgerSummary(int $businessId, int $customerId, ?Customer $customer = null): array
    {
        $table = 'contact_ledgers';
        $hasType = SchemaCache::hasColumn($table, 'type');
        $hasAccType = SchemaCache::hasColumn($table, 'acc_transaction_type');
        $hasAmount = SchemaCache::hasColumn($table, 'amount');
        $hasDescription = SchemaCache::hasColumn($table, 'description');
        $hasTransactionId = SchemaCache::hasColumn($table, 'transaction_id');
        $hasTransactions = $hasTransactionId && SchemaCache::hasTable('transactions');

        $typeExpression = $hasType
            ? "LOWER(COALESCE({$table}.type, ''))"
            : ($hasAccType ? "LOWER(COALESCE({$table}.acc_transaction_type, ''))" : "'debit'");
        $amountExpression = $hasAmount ? "COALESCE({$table}.amount, 0)" : '0';

        $openingChecks = [];
        if ($hasDescription) {
            $openingChecks[] = "LOWER(COALESCE({$table}.description, '')) LIKE '%opening%'";
        }
        if ($hasTransactions && SchemaCache::hasColumn('transactions', 'type')) {
            $openingChecks[] = "transactions.type = 'opening_balance'";
        }
        $openingExpression = empty($openingChecks) ? '0 = 1' : '(' . implode(' OR ', $openingChecks) . ')';

        $query = DB::table($table)
            ->where($table . '.business_id', $businessId)
            ->where($table . '.contact_id', $customerId);

        if ($hasTransactions) {
            $query->leftJoin('transactions', $table . '.transaction_id', '=', 'transactions.id');
        }

        if (SchemaCache::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }

        $result = $query->selectRaw(
            "COALESCE(SUM(CASE WHEN {$typeExpression} = 'credit' THEN 0 ELSE {$amountExpression} END), 0) as debit_total, " .
            "COALESCE(SUM(CASE WHEN {$typeExpression} = 'credit' THEN {$amountExpression} ELSE 0 END), 0) as credit_total, " .
            "MAX(CASE WHEN {$openingExpression} THEN 1 ELSE 0 END) as has_opening"
        )->first();

        $debit = (float) ($result->debit_total ?? 0);
        $credit = (float) ($result->credit_total ?? 0);

        if (!(bool) ($result->has_opening ?? false)) {
            $debit += $this->openingBalanceAmount($businessId, $customerId, $customer);
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $debit - $credit,
        ];
    }

    protected function openingBalanceAmount(int $businessId, int $customerId, ?Customer $customer = null): float
    {
        if (!SchemaCache::hasColumn('contacts', 'opening_balance')) {
            return 0.0;
        }

        if ($customer && (int) $customer->id === $customerId && (int) $customer->business_id === $businessId) {
            return (float) ($customer->opening_balance ?? 0);
        }

        $query = Customer::forBusiness($businessId)->where('id', $customerId);
        if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (float) $query->value('opening_balance');
    }


    protected function customerOpeningBalanceRow(int $businessId, int $customerId, ?Customer $customer = null)
    {
        if (!$customer || (int) $customer->id !== $customerId || (int) $customer->business_id !== $businessId) {
            $query = Customer::forBusiness($businessId)->where('id', $customerId);
            if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            $customer = $query->first();
        }

        if (!$customer) {
            return null;
        }

        $openingAmount = SchemaCache::hasColumn('contacts', 'opening_balance')
            ? (float) ($customer->opening_balance ?? 0)
            : 0.0;
        $transactionDate = null;
        $createdAt = $customer->created_at;

        if (SchemaCache::hasColumn('contacts', 'transaction_date') && !empty($customer->transaction_date)) {
            $transactionDate = $customer->transaction_date;
        }

        // Some tenant databases store Opening Balance only as a transaction,
        // while older records may have contacts.transaction_date out of sync
        // with the real Opening Balance transaction. Prefer the real transaction
        // date whenever it exists; use its amount only when the contact-level
        // opening balance is empty.
        if (SchemaCache::hasTable('transactions')
            && SchemaCache::hasColumn('transactions', 'type')
            && SchemaCache::hasColumn('transactions', 'final_total')) {
            $openingQuery = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereIn('type', ['opening_balance', 'fleet_opening_balance']);

            if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
                $openingQuery->whereNull('deleted_at');
            }

            $openingRecord = $openingQuery
                ->orderBy(SchemaCache::hasColumn('transactions', 'transaction_date') ? 'transaction_date' : 'id')
                ->first();

            if ($openingRecord) {
                if (abs($openingAmount) < 0.00001) {
                    $openingAmount = (float) ($openingRecord->final_total ?? 0);
                }
                $transactionDate = $openingRecord->transaction_date ?? $openingRecord->created_at ?? $transactionDate;
                $createdAt = $openingRecord->created_at ?? $createdAt;
            }
        }

        if (abs($openingAmount) < 0.00001) {
            return null;
        }

        $row = new \stdClass();
        $row->id = 'opening-' . $customer->id;
        $row->created_at = $createdAt;
        $row->transaction_date = $transactionDate ?: $createdAt;
        $row->description = 'Opening Balance';
        $row->invoice_no = 'Opening Balance';
        $row->ref_no = '';
        $row->transaction_type = 'opening_balance';
        $row->type = $openingAmount < 0 ? 'credit' : 'debit';
        $row->payment_status = $openingAmount == 0.0 ? 'paid' : 'due';
        $row->amount = abs($openingAmount);
        $row->acc_transaction_type = $openingAmount < 0 ? 'credit' : 'debit';
        $row->payment_method = '';
        $row->contact_id = $customer->id;
        $row->customer_name = $customer->name;
        $row->customer_code = $customer->contact_id;

        return $row;
    }

    /**
     * Put the correct first row on the ledger.
     *
     * 8049: the Opening Balance row used to be prepended whichever date range
     * was showing. Looking at, say, this month, the user saw an Opening Balance
     * dated years earlier sitting above transactions it had nothing to do with,
     * and every running balance beneath it was computed from a figure that did
     * not belong in the period.
     *
     * What goes on top now depends on where the opening date falls:
     *
     *   opening date INSIDE the range   - the Opening Balance row itself, as
     *                                     before. It genuinely happened in this
     *                                     period.
     *
     *   opening date BEFORE the range   - a "B/F Balance" row instead, carrying
     *                                     the closing balance of the day before
     *                                     the range starts. That figure is the
     *                                     opening balance plus every movement
     *                                     since, which is what the period has to
     *                                     start from for the running balance to
     *                                     mean anything.
     *
     *   opening date AFTER the range    - neither. It has not happened yet as
     *                                     far as this period is concerned.
     *
     * With no range selected the old behaviour is kept exactly: the whole
     * history is on screen, so the Opening Balance row is the honest first row
     * and there is nothing to bring forward.
     */
    protected function withOpeningBalanceRow(
        $rows,
        int $businessId,
        ?int $customerId,
        ?Customer $customer = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $collection = collect($rows);
        if (empty($customerId)) {
            return $collection;
        }

        $opening = $this->customerOpeningBalanceRow($businessId, $customerId, $customer);

        $hasOpeningInLedger = $collection->contains(function ($row) {
            $description = strtolower((string) ($row->description ?? ''));
            $type = strtolower((string) ($row->transaction_type ?? $row->type ?? ''));
            return str_contains($description, 'opening') || $type === 'opening_balance';
        });

        // If the filtered ledger already contains the genuine Opening Balance
        // row, it is authoritative for that period. Never add a synthetic
        // Opening/Brought-Forward row on top of it. This also protects older
        // tenants where contacts.transaction_date and transactions.transaction_date
        // were historically out of sync.
        if ($hasOpeningInLedger) {
            return $this->sortLedgerRows($collection);
        }

        $openingDate = $opening
            ? $this->ledgerRowDate($opening)
            : null;

        // IS2297: truly unfiltered means BOTH boundaries are empty. The
        // brought-forward calculation intentionally calls ledgerRows() with
        // only an end date (the day before the visible period). Treating that
        // internal query as unfiltered used to prepend a future Opening Balance
        // and then turn it into an incorrect B/F row, so the ledger displayed
        // the same opening amount twice.
        if (empty($startDate) && empty($endDate)) {
            if ($opening && !$hasOpeningInLedger) {
                $collection->prepend($opening);
            }

            return $this->sortLedgerRows($collection);
        }

        // End-date-only queries are used to calculate the prior closing
        // balance. Include the Opening Balance only when it had actually
        // occurred on or before that cut-off date.
        if (empty($startDate) && !empty($endDate)) {
            $openingIsOnOrBeforeEnd = $openingDate !== null && $openingDate <= $endDate;

            if ($opening && $openingIsOnOrBeforeEnd && !$hasOpeningInLedger) {
                $collection->prepend($opening);
            }

            return $this->sortLedgerRows($collection);
        }

        $openingIsInRange = $openingDate !== null
            && $openingDate >= $startDate
            && (empty($endDate) || $openingDate <= $endDate);

        if ($openingIsInRange) {
            if (!$hasOpeningInLedger) {
                $collection->prepend($opening);
            }

            return $this->sortLedgerRows($collection);
        }

        // The opening row is not part of this period. Anything that happened
        // before it starts is summarised into one brought-forward figure.
        $broughtForward = $this->broughtForwardRow($businessId, $customerId, $startDate, $customer);

        if ($broughtForward) {
            $collection->prepend($broughtForward);
        }

        return $this->sortLedgerRows($collection);
    }

    /**
     * Sort ledger rows by date, keeping a brought-forward row pinned to the top.
     *
     * 8049: B/F is dated the day before the range, so a plain date sort would
     * already place it first - but only until a row turns up with a blank or
     * older date, and then the opening figure would appear halfway down the
     * table with the running balance built on top of it. The flag makes the
     * position explicit rather than incidental.
     */
    protected function sortLedgerRows($collection)
    {
        return collect($collection)->sortBy(function ($row) {
            $isBroughtForward = !empty($row->is_brought_forward);

            return sprintf(
                '%d|%s',
                $isBroughtForward ? 0 : 1,
                (string) ($row->transaction_date ?? $row->created_at ?? '')
            );
        })->values();
    }

    /**
     * A row's effective date as Y-m-d, or null when it has none.
     */
    protected function ledgerRowDate($row): ?string
    {
        $value = $row->transaction_date ?? $row->created_at ?? null;

        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The closing balance of the day before the range, as a single ledger row.
     *
     * The figure is produced by asking for the ledger itself up to that day and
     * totalling it, rather than by a separate hand-written aggregate. It
     * therefore counts exactly the same rows the ledger would have shown for
     * that earlier period - contact_ledgers, the transactions the receivable
     * service adds back, and the opening balance - so the B/F figure and the
     * table can never disagree about what came before.
     *
     * The inner call passes no start date, which is what stops it recursing:
     * that path takes the unfiltered branch above and never asks for another
     * brought-forward figure.
     */
    protected function broughtForwardRow(
        int $businessId,
        int $customerId,
        string $startDate,
        ?Customer $customer = null
    ) {
        try {
            $priorEnd = \Carbon\Carbon::parse($startDate)->subDay();
        } catch (\Throwable $e) {
            return null;
        }

        $priorRows = $this->ledgerRows(
            $businessId,
            $customerId,
            5000,
            false,
            $customer,
            null,
            $priorEnd->format('Y-m-d')
        );

        $balance = 0.0;
        foreach ($priorRows as $row) {
            $amount = abs((float) ($row->amount ?? $row->final_total ?? 0));
            $type = strtolower((string) ($row->acc_transaction_type ?? $row->type ?? 'debit'));
            $balance += ($type === 'credit') ? -$amount : $amount;
        }

        // Nothing happened before this period, so there is nothing to bring
        // forward. A zero row would only add noise.
        if (abs($balance) < 0.00001) {
            return null;
        }

        $row = new \stdClass();
        $row->id = 'bf-' . $customerId;
        $row->is_brought_forward = true;
        $row->created_at = null;
        $row->transaction_date = $priorEnd->format('Y-m-d');
        $row->description = 'B/F Balance';
        $row->invoice_no = 'B/F Balance';
        $row->ref_no = '';
        $row->transaction_type = 'bf_balance';
        $row->type = $balance < 0 ? 'credit' : 'debit';
        $row->payment_status = '';
        $row->amount = abs($balance);
        $row->acc_transaction_type = $balance < 0 ? 'credit' : 'debit';
        $row->payment_method = '';
        $row->contact_id = $customerId;
        $row->customer_name = $customer->name ?? '';
        $row->customer_code = $customer->contact_id ?? '';

        return $row;
    }

    public function statementRows(int $businessId, ?int $customerId = null, int $limit = 500)
    {
        return $this->ledgerRows($businessId, $customerId, $limit);
    }



    public function portalSummary(int $businessId, int $customerId): array
    {
        $summary = [
            'invoice_total' => 0.0,
            'payment_total' => 0.0,
            'outstanding' => 0.0,
            'last_payment_date' => null,
            'invoice_count' => 0,
            'payment_count' => 0,
        ];

        if ($this->canUseTransactions()) {
            $invoiceQuery = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereIn('type', ['sell', 'opening_balance', 'direct_customer_loan'])
                ->whereNull('deleted_at');

            $summary['invoice_total'] = (float) (clone $invoiceQuery)->sum('final_total');
            $summary['invoice_count'] = (int) (clone $invoiceQuery)->count();
        }

        if ($this->canUseTransactionPayments()) {
            $paymentQuery = DB::table('transaction_payments')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->where(function ($query) use ($customerId) {
                    $query->where('payment_for', $customerId)
                        ->orWhereIn('transaction_id', function ($sub) use ($customerId, $businessId) {
                            $sub->select('id')
                                ->from('transactions')
                                ->where('business_id', $businessId)
                                ->where('contact_id', $customerId)
                                ->whereNull('deleted_at');
                        });
                });

            $summary['payment_total'] = (float) (clone $paymentQuery)->sum('amount');
            $summary['payment_count'] = (int) (clone $paymentQuery)->count();
            $summary['last_payment_date'] = (clone $paymentQuery)->orderByDesc('paid_on')->value('paid_on');
        }

        if (SchemaCache::hasTable('contact_ledgers')) {
            $ledgerBalance = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereNull('deleted_at')
                ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) as balance")
                ->value('balance');

            if ($ledgerBalance !== null) {
                $summary['outstanding'] = (float) $ledgerBalance;
            } else {
                $summary['outstanding'] = $summary['invoice_total'] - $summary['payment_total'];
            }
        } else {
            $summary['outstanding'] = $summary['invoice_total'] - $summary['payment_total'];
        }

        return $summary;
    }

    public function portalStatementRows(int $businessId, int $customerId, ?string $from = null, ?string $to = null, int $limit = 1000)
    {
        if (SchemaCache::hasTable('contact_ledgers')) {
            $query = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereNull('deleted_at')
                ->select([
                    'id',
                    'transaction_date',
                    'created_at',
                    'description',
                    'type',
                    'amount',
                    'transaction_id',
                ]);

            if (!empty($from)) {
                $query->whereDate('transaction_date', '>=', $from);
            }
            if (!empty($to)) {
                $query->whereDate('transaction_date', '<=', $to);
            }

            $rows = $query->orderBy('transaction_date')->orderBy('id')->limit($limit)->get();
            $balance = 0.0;

            return $rows->map(function ($row) use (&$balance) {
                $debit = $row->type === 'debit' ? (float) $row->amount : 0.0;
                $credit = $row->type === 'credit' ? (float) $row->amount : 0.0;
                $balance += $debit - $credit;

                return (object) [
                    'transaction_date' => !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '',
                    'system_datetime' => !empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '',
                    'reference' => $row->transaction_id ? 'TRX-' . $row->transaction_id : 'LED-' . $row->id,
                    'description' => $row->description ?: ucwords(str_replace('_', ' ', (string) $row->type)),
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                    'status' => $balance > 0 ? 'Due' : 'Paid',
                ];
            });
        }

        $transactions = $this->portalInvoices($businessId, $customerId, $limit);
        $payments = $this->portalPayments($businessId, $customerId, $limit);
        $items = collect();

        foreach ($transactions as $row) {
            $items->push((object) [
                'transaction_date' => !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '',
                'system_datetime' => !empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '',
                'reference' => $row->invoice_no ?: $row->ref_no,
                'description' => ucwords(str_replace('_', ' ', $row->type)),
                'debit' => (float) $row->final_total,
                'credit' => 0.0,
                'sort_key' => strtotime($row->transaction_date ?: $row->created_at ?: 'now'),
            ]);
        }

        foreach ($payments as $row) {
            $items->push((object) [
                'transaction_date' => !empty($row->paid_on) ? date('Y-m-d', strtotime($row->paid_on)) : '',
                'system_datetime' => !empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '',
                'reference' => $row->payment_ref_no ?: ('PAY-' . $row->id),
                'description' => 'Payment - ' . ucwords(str_replace('_', ' ', (string) $row->method)),
                'debit' => 0.0,
                'credit' => (float) $row->amount,
                'sort_key' => strtotime($row->paid_on ?: $row->created_at ?: 'now'),
            ]);
        }

        $balance = 0.0;
        return $items->sortBy('sort_key')->values()->map(function ($row) use (&$balance) {
            $balance += (float) $row->debit - (float) $row->credit;
            $row->balance = $balance;
            $row->status = $balance > 0 ? 'Due' : 'Paid';
            return $row;
        });
    }

    public function portalInvoices(int $businessId, int $customerId, int $limit = 1000)
    {
        if (!$this->canUseTransactions()) {
            return collect();
        }

        return DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereIn('type', ['sell', 'opening_balance', 'direct_customer_loan'])
            ->whereNull('deleted_at')
            ->select(['id', 'transaction_date', 'created_at', 'invoice_no', 'ref_no', 'type', 'payment_status', 'final_total'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function portalPayments(int $businessId, int $customerId, int $limit = 1000)
    {
        if (!$this->canUseTransactionPayments()) {
            return collect();
        }

        return DB::table('transaction_payments')
            ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transaction_payments.business_id', $businessId)
            ->whereNull('transaction_payments.deleted_at')
            ->where(function ($query) use ($customerId) {
                $query->where('transaction_payments.payment_for', $customerId)
                    ->orWhere('transactions.contact_id', $customerId);
            })
            ->select([
                'transaction_payments.id',
                'transaction_payments.paid_on',
                'transaction_payments.created_at',
                'transaction_payments.payment_ref_no',
                'transaction_payments.method',
                'transaction_payments.amount',
                'transaction_payments.note',
                'transactions.invoice_no',
            ])
            ->orderByDesc('transaction_payments.paid_on')
            ->orderByDesc('transaction_payments.id')
            ->limit($limit)
            ->get();
    }



    public function portalCreditSummary(int $businessId, int $customerId): array
    {
        $customer = Customer::where('business_id', $businessId)->find($customerId);
        $summary = $this->portalSummary($businessId, $customerId);
        $creditLimit = (float) ($customer->credit_limit ?? 0);
        $outstanding = (float) ($summary['outstanding'] ?? 0);

        return [
            'current_balance' => $outstanding,
            'outstanding_amount' => max($outstanding, 0),
            'credit_limit' => $creditLimit,
            'available_credit' => $creditLimit > 0 ? max($creditLimit - max($outstanding, 0), 0) : 0,
            'last_payment_date' => $summary['last_payment_date'] ?? null,
            'invoice_count' => $summary['invoice_count'] ?? 0,
            'payment_count' => $summary['payment_count'] ?? 0,
        ];
    }

    public function portalOrders(int $businessId, int $customerId, int $limit = 1000)
    {
        if (!$this->canUseTransactions()) {
            return collect();
        }

        $types = ['sell_order', 'sales_order', 'order'];

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($types) {
                $q->whereIn('type', $types);

                if (SchemaCache::hasColumn('transactions', 'sub_type')) {
                    $q->orWhereIn('sub_type', $types);
                }
            });

        $select = [
            'id',
            'transaction_date',
            'created_at',
            'invoice_no',
            'ref_no',
            'type',
            'status',
            'payment_status',
            'final_total',
        ];

        if (SchemaCache::hasColumn('transactions', 'total_before_tax')) {
            $select[] = 'total_before_tax';
        }

        $rows = $query->select($select)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        // CUS_OPT_001: avoid one payment query and one quantity query per order row.
        // Batch totals keep the portal orders page responsive for large customers.
        $transactionIds = $rows->pluck('id')->map(function ($id) {
            return (int) $id;
        })->filter()->values()->all();

        $paidByTransaction = $this->paidForTransactions($businessId, $transactionIds);
        $quantityByTransaction = $this->quantitiesForTransactions($transactionIds);

        return $rows->map(function ($row) use ($paidByTransaction, $quantityByTransaction) {
            $transactionId = (int) $row->id;
            $paid = (float) ($paidByTransaction[$transactionId] ?? 0);
            $row->paid_amount = $paid;
            $row->balance = max(((float) $row->final_total) - $paid, 0);
            $row->qty = (float) ($quantityByTransaction[$transactionId] ?? 0);
            $row->order_no = $row->invoice_no ?: ($row->ref_no ?: 'ORD-' . $row->id);
            return $row;
        });
    }

    public function portalOutstandingInvoices(int $businessId, int $customerId, int $limit = 1000)
    {
        $rows = $this->portalInvoices($businessId, $customerId, $limit);
        $today = strtotime(date('Y-m-d'));

        // CUS_OPT_001: batch-load payments for all visible invoices instead of
        // running one sum query for each invoice row.
        $transactionIds = $rows->pluck('id')->map(function ($id) {
            return (int) $id;
        })->filter()->values()->all();

        $paidByTransaction = $this->paidForTransactions($businessId, $transactionIds);

        return $rows->map(function ($row) use ($paidByTransaction, $today) {
            $paid = (float) ($paidByTransaction[(int) $row->id] ?? 0);
            $balance = max(((float) $row->final_total) - $paid, 0);
            $date = !empty($row->transaction_date) ? strtotime(date('Y-m-d', strtotime($row->transaction_date))) : $today;
            $days = max((int) floor(($today - $date) / 86400), 0);

            $row->paid_amount = $paid;
            $row->balance = $balance;
            $row->days_outstanding = $days;
            $row->aging_bucket = $days <= 30 ? '0-30 Days' : ($days <= 60 ? '31-60 Days' : '61+ Days');
            $row->aging_class = $days <= 30 ? 'dd-aging-good' : ($days <= 60 ? 'dd-aging-warning' : 'dd-aging-danger');
            return $row;
        })->filter(function ($row) {
            return (float) $row->balance > 0.00001;
        })->values();
    }

    protected function paidForTransaction(int $businessId, int $transactionId): float
    {
        if (!$this->canUseTransactionPayments()) {
            return 0.0;
        }

        return (float) DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->where('transaction_id', $transactionId)
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    /**
     * CUS_OPT_001
     * Batch payment totals for a list of transactions.
     * This prevents N+1 sum queries on portal orders and outstanding invoices.
     */
    protected function paidForTransactions(int $businessId, array $transactionIds): array
    {
        $transactionIds = array_values(array_unique(array_filter(array_map('intval', $transactionIds))));

        if (empty($transactionIds) || !$this->canUseTransactionPayments()) {
            return [];
        }

        return DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->whereIn('transaction_id', $transactionIds)
            ->whereNull('deleted_at')
            ->select('transaction_id', DB::raw('SUM(amount) as paid_amount'))
            ->groupBy('transaction_id')
            ->pluck('paid_amount', 'transaction_id')
            ->map(function ($amount) {
                return (float) $amount;
            })
            ->toArray();
    }

    protected function transactionQuantity(int $transactionId): float
    {
        if (!SchemaCache::hasTable('transaction_sell_lines') || !SchemaCache::hasColumn('transaction_sell_lines', 'quantity')) {
            return 0.0;
        }

        return (float) DB::table('transaction_sell_lines')
            ->where('transaction_id', $transactionId)
            ->whereNull('deleted_at')
            ->sum('quantity');
    }

    /**
     * CUS_OPT_001
     * Batch line quantities for a list of transactions.
     */
    protected function quantitiesForTransactions(array $transactionIds): array
    {
        $transactionIds = array_values(array_unique(array_filter(array_map('intval', $transactionIds))));

        if (empty($transactionIds) || !SchemaCache::hasTable('transaction_sell_lines') || !SchemaCache::hasColumn('transaction_sell_lines', 'quantity')) {
            return [];
        }

        return DB::table('transaction_sell_lines')
            ->whereIn('transaction_id', $transactionIds)
            ->whereNull('deleted_at')
            ->select('transaction_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('transaction_id')
            ->pluck('total_quantity', 'transaction_id')
            ->map(function ($quantity) {
                return (float) $quantity;
            })
            ->toArray();
    }



    public function portalNotifications(int $businessId, int $customerId, int $limit = 50)
    {
        $items = collect();

        foreach ($this->portalInvoices($businessId, $customerId, 15) as $row) {
            $items->push((object) [
                'date' => !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '',
                'type' => 'New Invoice',
                'title' => 'Invoice ' . ($row->invoice_no ?: $row->ref_no ?: ('TRX-' . $row->id)),
                'message' => 'Invoice amount: ' . number_format((float) $row->final_total, 2),
                'status' => strtolower((string) $row->payment_status) === 'paid' ? 'Read' : 'Unread',
            ]);
        }

        foreach ($this->portalPayments($businessId, $customerId, 15) as $row) {
            $items->push((object) [
                'date' => !empty($row->paid_on) ? date('Y-m-d', strtotime($row->paid_on)) : '',
                'type' => 'Payment Received',
                'title' => 'Payment ' . ($row->payment_ref_no ?: ('PAY-' . $row->id)),
                'message' => 'Payment amount: ' . number_format((float) $row->amount, 2),
                'status' => 'Read',
            ]);
        }

        $summary = $this->portalCreditSummary($businessId, $customerId);
        $creditLimit = (float) ($summary['credit_limit'] ?? 0);
        $outstanding = (float) ($summary['outstanding_amount'] ?? 0);
        if ($creditLimit > 0 && $outstanding >= ($creditLimit * 0.8)) {
            $items->push((object) [
                'date' => date('Y-m-d'),
                'type' => 'Credit Limit Warning',
                'title' => 'Credit utilization is high',
                'message' => 'Outstanding balance is close to the approved credit limit.',
                'status' => 'Unread',
            ]);
        }

        return $items->sortByDesc('date')->values()->take($limit);
    }

    public function portalAnnouncements(int $businessId, int $customerId, int $limit = 50)
    {
        $items = collect();

        foreach (['announcements', 'customer_announcements', 'dealer_announcements'] as $table) {
            if (!SchemaCache::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);
            if (SchemaCache::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (SchemaCache::hasColumn($table, 'is_active')) {
                $query->where('is_active', 1);
            }
            if (SchemaCache::hasColumn($table, 'expiry_date')) {
                $query->where(function ($q) use ($table) {
                    $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', date('Y-m-d'));
                });
            }

            $rows = $query->orderByDesc(SchemaCache::hasColumn($table, 'created_at') ? 'created_at' : 'id')->limit($limit)->get();
            foreach ($rows as $row) {
                $items->push((object) [
                    'date' => !empty($row->created_at) ? date('Y-m-d', strtotime($row->created_at)) : date('Y-m-d'),
                    'title' => $row->title ?? $row->subject ?? 'Announcement',
                    'message' => $row->message ?? $row->description ?? $row->content ?? '',
                    'attachment' => $row->attachment ?? $row->file ?? null,
                ]);
            }

            if ($items->count() > 0) {
                break;
            }
        }

        return $items->sortByDesc('date')->values()->take($limit);
    }

    public function portalMessages(int $businessId, int $customerId, int $limit = 50)
    {
        $items = collect();

        foreach (['customer_messages', 'dealer_messages', 'customer_communications'] as $table) {
            if (!SchemaCache::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);
            if (SchemaCache::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (SchemaCache::hasColumn($table, 'contact_id')) {
                $query->where('contact_id', $customerId);
            } elseif (SchemaCache::hasColumn($table, 'customer_id')) {
                $query->where('customer_id', $customerId);
            }

            $rows = $query->orderByDesc(SchemaCache::hasColumn($table, 'created_at') ? 'created_at' : 'id')->limit($limit)->get();
            foreach ($rows as $row) {
                $items->push((object) [
                    'date' => !empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '',
                    'from' => $row->from_name ?? $row->sender_name ?? $row->created_by_name ?? 'ERP Team',
                    'subject' => $row->subject ?? $row->title ?? 'Message',
                    'message' => $row->message ?? $row->description ?? $row->notes ?? '',
                    'status' => !empty($row->read_at) ? 'Read' : 'Unread',
                ]);
            }

            if ($items->count() > 0) {
                break;
            }
        }

        return $items->sortByDesc('date')->values()->take($limit);
    }

    public function portalDocuments(int $businessId, int $customerId, int $limit = 50)
    {
        $items = collect();

        foreach ($this->portalInvoices($businessId, $customerId, 20) as $row) {
            $items->push((object) [
                'date' => !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '',
                'category' => 'Invoice',
                'title' => $row->invoice_no ?: $row->ref_no ?: ('Invoice ' . $row->id),
                'description' => 'Invoice amount: ' . number_format((float) $row->final_total, 2),
                'url' => null,
            ]);
        }

        foreach ($this->portalPayments($businessId, $customerId, 20) as $row) {
            $items->push((object) [
                'date' => !empty($row->paid_on) ? date('Y-m-d', strtotime($row->paid_on)) : '',
                'category' => 'Receipt',
                'title' => $row->payment_ref_no ?: ('Receipt ' . $row->id),
                'description' => 'Payment amount: ' . number_format((float) $row->amount, 2),
                'url' => null,
            ]);
        }

        return $items->sortByDesc('date')->values()->take($limit);
    }

    public function agingSummary(int $businessId): array
    {
        $buckets = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'over_90' => 0.0,
        ];

        // S347: Customer Aging must reconcile with the same ledger balance used by
        // Customer List Total Due and Dashboard Outstanding.  The previous version
        // aged unpaid transaction final_total values only, so payments, opening
        // balances and direct ledger adjustments made the buckets incorrect.
        if (SchemaCache::hasTable('contacts')) {
            $customers = DB::table('contacts')
                ->where('business_id', $businessId)
                ->where('type', 'customer')
                ->when($this->contactsHasColumn('deleted_at'), function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->select(['id'])
                ->get();

            foreach ($customers as $customer) {
                $summary = $this->ledgerSummary($businessId, (int) $customer->id);
                $balance = (float) ($summary['balance'] ?? 0);

                if ($balance <= 0) {
                    continue;
                }

                $lastDate = $this->customerLastLedgerDate($businessId, (int) $customer->id);
                $bucket = $this->agingBucketFromDate($lastDate);
                $buckets[$bucket] += $balance;
            }

            return $buckets;
        }

        return $buckets;
    }

    protected function customerLastLedgerDate(int $businessId, int $customerId): ?string
    {
        if (SchemaCache::hasTable('contact_ledgers')) {
            $dateColumn = SchemaCache::hasColumn('contact_ledgers', 'operation_date') ? 'operation_date' : 'created_at';
            $query = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId);

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $date = $query->orderByDesc($dateColumn)->value($dateColumn);
            if (!empty($date)) {
                return (string) $date;
            }
        }

        if (SchemaCache::hasTable('transactions') && SchemaCache::hasColumn('transactions', 'transaction_date')) {
            $date = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereIn('type', $this->customerTransactionTypes())
                ->when(SchemaCache::hasColumn('transactions', 'deleted_at'), function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->orderByDesc('transaction_date')
                ->value('transaction_date');

            if (!empty($date)) {
                return (string) $date;
            }
        }

        if (SchemaCache::hasTable('contacts')) {
            $dateColumn = SchemaCache::hasColumn('contacts', 'transaction_date') ? 'transaction_date' : 'created_at';
            return DB::table('contacts')
                ->where('business_id', $businessId)
                ->where('id', $customerId)
                ->value($dateColumn);
        }

        return date('Y-m-d');
    }

    protected function agingBucketFromDate(?string $date): string
    {
        $today = strtotime(date('Y-m-d'));
        $date = !empty($date) ? strtotime(date('Y-m-d', strtotime($date))) : $today;
        $age = max(0, (int) floor(($today - $date) / 86400));

        if ($age <= 0) {
            return 'current';
        }
        if ($age <= 30) {
            return 'days_1_30';
        }
        if ($age <= 60) {
            return 'days_31_60';
        }
        if ($age <= 90) {
            return 'days_61_90';
        }

        return 'over_90';
    }

    protected function canUseTransactions(): bool
    {
        return SchemaCache::hasTable('transactions')
            && SchemaCache::hasColumn('transactions', 'business_id')
            && SchemaCache::hasColumn('transactions', 'final_total')
            && SchemaCache::hasColumn('transactions', 'type');
    }

    protected function canUseTransactionPayments(): bool
    {
        return SchemaCache::hasTable('transaction_payments')
            && SchemaCache::hasColumn('transaction_payments', 'business_id')
            && SchemaCache::hasColumn('transaction_payments', 'amount');
    }

    /**
     * Customer filter options for the Customer Ledger report.
     * Includes active and inactive customer records, while excluding deleted
     * contacts, so the report's "All" option truly represents all customers.
     */
    public function reportCustomerOptions(int $businessId): array
    {
        if (!SchemaCache::hasTable('contacts')) {
            return [];
        }

        $query = DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both']);

        if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $nameColumn = SchemaCache::hasColumn('contacts', 'name') ? 'name' : 'id';
        $columns = ['id', $nameColumn];
        if (SchemaCache::hasColumn('contacts', 'contact_id')) {
            $columns[] = 'contact_id';
        }
        if (SchemaCache::hasColumn('contacts', 'active')) {
            $columns[] = 'active';
        }

        return $query
            ->orderBy($nameColumn)
            ->get(array_values(array_unique($columns)))
            ->mapWithKeys(function ($customer) use ($nameColumn) {
                $name = trim((string) ($customer->{$nameColumn} ?? ''));
                $code = trim((string) ($customer->contact_id ?? ''));
                $inactive = isset($customer->active) && (int) $customer->active === 0;
                $label = trim(($code !== '' ? $code . ' - ' : '') . ($name !== '' ? $name : ('Customer ' . $customer->id)));
                if ($inactive) {
                    $label .= ' (Inactive)';
                }

                return [(int) $customer->id => $label];
            })
            ->toArray();
    }

    /**
     * Add one brought-forward Opening Balance row for every customer when the
     * report is displaying All customers. This is done with two bulk queries,
     * avoiding an N+1 query for each customer.
     */
    public function withAllCustomerOpeningBalances($rows, int $businessId)
    {
        $collection = collect($rows)->values();
        if (!SchemaCache::hasTable('contacts')) {
            return $collection;
        }

        $represented = [];
        foreach ($collection as $row) {
            $description = strtolower((string) ($row->description ?? ''));
            $type = strtolower((string) ($row->transaction_type ?? $row->type ?? ''));
            if ($type === 'opening_balance' || $type === 'fleet_opening_balance' || str_contains($description, 'opening balance')) {
                $represented[(int) ($row->contact_id ?? 0)] = true;
            }
        }

        $contactColumns = ['id'];
        foreach (['contact_id', 'name', 'opening_balance', 'transaction_date', 'created_at'] as $column) {
            if (SchemaCache::hasColumn('contacts', $column)) {
                $contactColumns[] = $column;
            }
        }

        $contacts = DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->when(SchemaCache::hasColumn('contacts', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->get(array_values(array_unique($contactColumns)));

        $transactionOpenings = collect();
        if (SchemaCache::hasTable('transactions')
            && SchemaCache::hasColumn('transactions', 'contact_id')
            && SchemaCache::hasColumn('transactions', 'type')
            && SchemaCache::hasColumn('transactions', 'final_total')) {
            $transactionColumns = ['id', 'contact_id', 'final_total'];
            foreach (['transaction_date', 'created_at'] as $column) {
                if (SchemaCache::hasColumn('transactions', $column)) {
                    $transactionColumns[] = $column;
                }
            }

            $transactionOpenings = DB::table('transactions')
                ->where('business_id', $businessId)
                ->whereIn('type', ['opening_balance', 'fleet_opening_balance'])
                ->when(SchemaCache::hasColumn('transactions', 'deleted_at'), function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->get(array_values(array_unique($transactionColumns)))
                ->groupBy(function ($row) {
                    return (int) $row->contact_id;
                });
        }

        foreach ($contacts as $customer) {
            $customerId = (int) $customer->id;
            if (!empty($represented[$customerId])) {
                continue;
            }

            $openingAmount = isset($customer->opening_balance) ? (float) $customer->opening_balance : 0.0;
            $openingDate = $customer->transaction_date ?? $customer->created_at ?? null;
            $createdAt = $customer->created_at ?? $openingDate;

            if (abs($openingAmount) < 0.00001 && $transactionOpenings->has($customerId)) {
                $openingRows = $transactionOpenings->get($customerId);
                $openingAmount = (float) $openingRows->sum(function ($row) {
                    return (float) ($row->final_total ?? 0);
                });
                $firstOpening = $openingRows->sortBy(function ($row) {
                    return (string) ($row->transaction_date ?? $row->created_at ?? $row->id ?? '');
                })->first();
                $openingDate = $firstOpening->transaction_date ?? $firstOpening->created_at ?? $openingDate;
                $createdAt = $firstOpening->created_at ?? $createdAt;
            }

            if (abs($openingAmount) < 0.00001) {
                continue;
            }

            $row = new \stdClass();
            $row->id = 'opening-' . $customerId;
            $row->created_at = $createdAt;
            $row->transaction_date = $openingDate ?: $createdAt;
            $row->description = 'Opening Balance';
            $row->invoice_no = 'Opening Balance';
            $row->ref_no = '';
            $row->transaction_type = 'opening_balance';
            $row->type = $openingAmount < 0 ? 'credit' : 'debit';
            $row->payment_status = abs($openingAmount) < 0.00001 ? 'paid' : 'due';
            $row->amount = abs($openingAmount);
            $row->acc_transaction_type = $openingAmount < 0 ? 'credit' : 'debit';
            $row->payment_method = '';
            $row->transaction_payment_id = null;
            $row->transaction_id = null;
            $row->payment_ref_no = '';
            $row->paid_in_type = '';
            $row->contact_id = $customerId;
            $row->customer_name = $customer->name ?? ('Customer ' . $customerId);
            $row->customer_code = $customer->contact_id ?? '';
            $row->is_opening_balance = true;
            $collection->prepend($row);
        }

        return $collection->values();
    }

    /**
     * Attach invoice-level allocations to the aggregate Bulk Payment ledger row.
     * A single query retrieves all selected bills for every visible Bulk Payment
     * reference, so the Bills popup is instant and does not add row-by-row calls.
     */
    public function withBulkPaymentBillDetails($rows, int $businessId)
    {
        $collection = collect($rows)->values();

        foreach ($collection as $row) {
            $row->bulk_payment_bills = [];
        }

        // 2026-09-23: legacy Customer Bulk Payment rows in older tenant DBs are
        // not tagged paid_in_type=customer_bulk. They are stored as one direct
        // parent receipt (transaction_id NULL, payment_for=customer) plus child
        // invoice allocations linked by parent_id. Normalise that shape first.
        $collection = $this->normaliseParentChildBulkPaymentRows($collection, $businessId);

        if (!SchemaCache::hasTable('transaction_payments')
            || !SchemaCache::hasColumn('transaction_payments', 'payment_ref_no')) {
            return $collection;
        }

        /*
         |--------------------------------------------------------------------------
         | Customer Bulk Payment display normalisation
         |--------------------------------------------------------------------------
         |
         | One Bulk Payment is persisted in two useful shapes:
         |   1) transaction_payments = one row per invoice allocation; and
         |   2) contact_ledgers      = one aggregate credit for the whole receipt.
         |
         | Showing both shapes in the Customer Ledger overstates the visible credit.
         | For the ledger UI we therefore replace every visible aggregate Bulk Payment
         | credit with canonical invoice-allocation rows built from transaction_payments.
         |
         | No database row is edited or deleted here.  The sum of the replacement
         | principal/unallocated credits is exactly the same customer-credit amount as
         | the aggregate row; interest is deliberately excluded from the customer
         | receivable credit, matching CustomerBulkPaymentService::save().
         */
        $references = $collection
            ->map(function ($row) {
                $reference = trim((string) ($row->payment_ref_no ?? ''));
                $description = trim((string) ($row->description ?? ''));
                $paidInType = strtolower(trim((string) ($row->paid_in_type ?? '')));
                $isBulkPayment = $paidInType === 'customer_bulk'
                    || str_contains(strtolower($description), 'bulk payment');

                if (!$isBulkPayment) {
                    return '';
                }

                // Older contact_ledgers tables may not have transaction_payment_id;
                // recover the CPB reference from the persisted description instead.
                if ($reference === '' && preg_match('/bulk\s+payment\s+([a-z0-9_\/-]+)/i', $description, $matches)) {
                    $reference = trim((string) ($matches[1] ?? ''));
                    $row->payment_ref_no = $reference;
                }

                return $reference;
            })
            ->filter()
            ->unique()
            ->values();

        if ($references->isEmpty()) {
            return $collection;
        }

        $has = fn (string $column) => SchemaCache::hasColumn('transaction_payments', $column);
        $hasTransactions = SchemaCache::hasTable('transactions') && $has('transaction_id');

        $query = DB::table('transaction_payments as bulk_tp')
            ->where('bulk_tp.business_id', $businessId)
            ->whereIn('bulk_tp.payment_ref_no', $references->all());

        if ($has('deleted_at')) {
            $query->whereNull('bulk_tp.deleted_at');
        }
        if ($has('paid_in_type')) {
            $query->where('bulk_tp.paid_in_type', 'customer_bulk');
        }

        if ($hasTransactions) {
            $query->leftJoin('transactions as bulk_tx', 'bulk_tp.transaction_id', '=', 'bulk_tx.id');
        }

        // Keep this query scoped to the customer represented by the already-loaded
        // ledger whenever the schema provides enough information to do so.
        $customerIds = $collection->pluck('contact_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $canScopeByPaymentFor = $has('payment_for');
        $canScopeByTransactionContact = $hasTransactions && SchemaCache::hasColumn('transactions', 'contact_id');
        if ($customerIds->isNotEmpty() && ($canScopeByPaymentFor || $canScopeByTransactionContact)) {
            $query->where(function ($customerScope) use ($customerIds, $hasTransactions, $has) {
                $hasCustomerCondition = false;

                if ($has('payment_for')) {
                    $customerScope->whereIn('bulk_tp.payment_for', $customerIds->all());
                    $hasCustomerCondition = true;
                }

                if ($hasTransactions && SchemaCache::hasColumn('transactions', 'contact_id')) {
                    if ($hasCustomerCondition) {
                        $customerScope->orWhereIn('bulk_tx.contact_id', $customerIds->all());
                    } else {
                        $customerScope->whereIn('bulk_tx.contact_id', $customerIds->all());
                    }
                }
            });
        }

        $selectPayment = function (string $column, ?string $alias = null) use ($has) {
            $alias = $alias ?: $column;
            return $has($column)
                ? 'bulk_tp.' . $column
                : DB::raw('NULL as ' . $alias);
        };
        $selectTransaction = function (string $column, ?string $alias = null) use ($hasTransactions) {
            $alias = $alias ?: $column;
            return $hasTransactions && SchemaCache::hasColumn('transactions', $column)
                ? 'bulk_tx.' . $column . ' as ' . $alias
                : DB::raw('NULL as ' . $alias);
        };

        $bulkPayments = $query->select([
                'bulk_tp.id',
                $selectPayment('transaction_id'),
                $selectPayment('amount'),
                $selectPayment('interest'),
                $selectPayment('method'),
                $selectPayment('paid_on'),
                $selectPayment('created_at'),
                'bulk_tp.payment_ref_no',
                $selectPayment('payment_for'),
                $selectPayment('cheque_number'),
                $selectPayment('cheque_date'),
                $selectPayment('bank_name'),
                $selectPayment('card_number'),
                $selectPayment('card_type'),
                $selectPayment('card_holder_name'),
                $selectPayment('card_transaction_number'),
                $selectPayment('reference_no'),
                $selectPayment('transaction_no'),
                $selectPayment('transfer_date'),
                $selectPayment('note'),
                $selectTransaction('invoice_no', 'invoice_no'),
                $selectTransaction('ref_no', 'transaction_ref_no'),
                $selectTransaction('type', 'source_transaction_type'),
                $selectTransaction('payment_status', 'source_payment_status'),
                $selectTransaction('contact_id', 'transaction_contact_id'),
                $selectTransaction('customer_ref', 'customer_ref'),
                $selectTransaction('sale_ref', 'sale_ref'),
            ])
            ->orderBy('bulk_tp.payment_ref_no')
            ->orderBy('bulk_tp.id')
            ->get()
            ->groupBy('payment_ref_no');

        if ($bulkPayments->isEmpty()) {
            return $collection;
        }

        $referencesToReplace = $bulkPayments->keys()->map(fn ($reference) => trim((string) $reference))->filter()->all();

        // Remove both the persisted aggregate row and any already-surfaced bulk
        // allocation rows. They are rebuilt once below, so old/new schemas render
        // exactly one customer credit per invoice allocation.
        $collection = $collection->reject(function ($row) use ($referencesToReplace) {
            $reference = trim((string) ($row->payment_ref_no ?? ''));
            $description = trim((string) ($row->description ?? ''));
            if ($reference === '' && preg_match('/bulk\s+payment\s+([a-z0-9_\/-]+)/i', $description, $matches)) {
                $reference = trim((string) ($matches[1] ?? ''));
            }

            if ($reference === '' || !in_array($reference, $referencesToReplace, true)) {
                return false;
            }

            $ledgerType = strtolower(trim((string) ($row->acc_transaction_type ?? $row->type ?? '')));

            // Once the reference is confirmed as a customer_bulk receipt from
            // transaction_payments, every visible credit carrying that exact
            // reference belongs to the same logical receipt. Remove all of them
            // before inserting the canonical per-invoice rows below.
            return $ledgerType === 'credit';
        })->values();

        $replacementRows = collect();

        foreach ($bulkPayments as $reference => $payments) {
            $reference = trim((string) $reference);
            $payments = collect($payments)->values();

            // The user-entered Lump Sum equals principal + interest + any
            // unallocated advance amount across the stored allocation rows.
            $lumpSumAmount = (float) $payments->sum(function ($payment) {
                return (float) ($payment->amount ?? 0) + (float) ($payment->interest ?? 0);
            });

            foreach ($payments as $payment) {
                $principal = abs((float) ($payment->amount ?? 0));
                if ($principal < 0.00001) {
                    continue;
                }

                $transactionId = (int) ($payment->transaction_id ?? 0);
                $invoiceNo = trim((string) ($payment->invoice_no ?? ''));
                $transactionRef = trim((string) ($payment->transaction_ref_no ?? ''));
                $sourceType = strtolower(trim((string) ($payment->source_transaction_type ?? '')));

                if ($transactionId > 0) {
                    if (in_array($sourceType, ['opening_balance', 'fleet_opening_balance'], true)) {
                        $description = 'Opening Balance';
                    } elseif ($invoiceNo !== '') {
                        $description = 'Bill No: ' . $invoiceNo;
                    } elseif ($transactionRef !== '') {
                        $description = 'Bill No: ' . $transactionRef;
                    } else {
                        $description = 'Transaction #' . $transactionId;
                    }
                } else {
                    // Preserve a genuine unallocated customer advance separately;
                    // never re-show the full aggregate Bulk Payment amount.
                    $description = 'Unallocated Bulk Payment Advance: ' . $reference;
                }

                $row = new \stdClass();
                $row->id = 'bulk-payment-' . (int) $payment->id;
                $row->created_at = $payment->created_at ?? $payment->paid_on ?? null;
                $row->transaction_date = $payment->paid_on ?? $payment->created_at ?? null;
                $row->description = $description;
                $row->ledger_note = $payment->note ?? '';
                $row->invoice_no = $invoiceNo;
                $row->ref_no = $transactionRef;
                $row->transaction_type = $transactionId > 0 ? 'payment' : 'advance_payment';
                $row->type = 'credit';
                $row->payment_status = trim((string) ($payment->source_payment_status ?? '')) ?: 'paid';
                $row->amount = $principal;
                $row->acc_transaction_type = 'credit';
                $row->payment_method = trim((string) ($payment->method ?? ''));
                $row->transaction_payment_id = (int) $payment->id;
                $row->transaction_id = $transactionId > 0 ? $transactionId : null;
                $row->payment_ref_no = $reference;
                $row->paid_in_type = 'customer_bulk';
                $row->contact_id = (int) ($payment->transaction_contact_id ?? $payment->payment_for ?? 0);
                $row->customer_name = '';
                $row->customer_code = '';
                $row->cheque_number = $payment->cheque_number ?? '';
                $row->cheque_date = $payment->cheque_date ?? null;
                $row->bank_name = $payment->bank_name ?? '';
                $row->transfer_date = $payment->transfer_date ?? null;
                $row->voucher_no = trim((string) ($payment->transaction_no ?? '')) !== ''
                    ? $payment->transaction_no
                    : ($payment->reference_no ?? '');
                $row->payment_reference_no = $payment->reference_no ?? '';
                $row->card_number = $payment->card_number ?? '';
                $row->card_type = $payment->card_type ?? '';
                $row->card_holder_name = $payment->card_holder_name ?? '';
                $row->card_transaction_number = $payment->card_transaction_number ?? '';
                $row->payment_note = $payment->note ?? '';
                $row->customer_ref = $payment->customer_ref ?? '';
                $row->sale_ref = $payment->sale_ref ?? '';
                $row->bulk_payment_bills = [];
                $row->is_bulk_payment_allocation = $transactionId > 0;
                $row->is_bulk_payment_unallocated = $transactionId <= 0;
                $row->bulk_lump_sum_amount = $lumpSumAmount;
                $row->bulk_payment_reference = $reference;

                $replacementRows->push($row);
            }
        }

        return $collection
            ->concat($replacementRows)
            ->sortBy(function ($row) {
                $transactionDate = (string) ($row->transaction_date ?? $row->created_at ?? '');
                $createdAt = (string) ($row->created_at ?? $transactionDate);
                $id = (int) preg_replace('/\D+/', '', (string) ($row->id ?? 0));

                return sprintf('%s|%s|%020d', $transactionDate, $createdAt, $id);
            })
            ->values();
    }

    /**
     * Normalise the legacy parent/child Customer Bulk Payment storage shape.
     *
     * Older tenant data can store one real lump-sum receipt as:
     *   - parent: transaction_id NULL, payment_for = customer, amount = full receipt;
     *   - children: one row per invoice allocation, parent_id = parent.id.
     *
     * The parent is the cash/cheque/card receipt. The children are allocation
     * detail. Showing parent + children double counts the receipt; showing only
     * children loses any unallocated customer advance. For Customer Ledger we
     * therefore display the children plus ONE residual advance row equal to:
     *
     *     parent receipt - sum(invoice allocations)
     *
     * No database row is edited or deleted.
     */
    protected function normaliseParentChildBulkPaymentRows($rows, int $businessId)
    {
        $collection = collect($rows)->values();

        if ($collection->isEmpty()
            || !SchemaCache::hasTable('transaction_payments')
            || !SchemaCache::hasColumn('transaction_payments', 'parent_id')
            || !SchemaCache::hasColumn('transaction_payments', 'transaction_id')
            || !SchemaCache::hasColumn('transaction_payments', 'amount')) {
            return $collection;
        }

        $visiblePaymentIds = $collection->pluck('transaction_payment_id')
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($visiblePaymentIds->isEmpty()) {
            return $collection;
        }

        // Find only visible child allocations whose parent is a direct customer
        // receipt. This avoids reclassifying unrelated parent/child payments.
        $candidate = DB::table('transaction_payments as legacy_child')
            ->join('transaction_payments as legacy_parent', 'legacy_parent.id', '=', 'legacy_child.parent_id')
            ->whereIn('legacy_child.id', $visiblePaymentIds->all())
            ->whereNotNull('legacy_child.parent_id')
            ->where('legacy_child.parent_id', '>', 0)
            ->whereNull('legacy_parent.transaction_id');

        if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
            $candidate->where('legacy_child.business_id', $businessId)
                ->where('legacy_parent.business_id', $businessId);
        }
        if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            $candidate->whereNull('legacy_child.deleted_at')
                ->whereNull('legacy_parent.deleted_at');
        }
        if (SchemaCache::hasColumn('transaction_payments', 'payment_for')) {
            $candidate->whereNotNull('legacy_parent.payment_for');
        }
        // Current/new bulk rows already have their own customer_bulk normaliser
        // below. This branch is specifically for the older customer_page shape.
        if (SchemaCache::hasColumn('transaction_payments', 'paid_in_type')) {
            $candidate->where(function ($q) {
                $q->whereNull('legacy_parent.paid_in_type')
                    ->orWhere('legacy_parent.paid_in_type', '!=', 'customer_bulk');
            });
        }

        $parentIds = $candidate->pluck('legacy_child.parent_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($parentIds->isEmpty()) {
            return $collection;
        }

        $has = fn (string $column) => SchemaCache::hasColumn('transaction_payments', $column);
        $hasTransactions = SchemaCache::hasTable('transactions');

        $query = DB::table('transaction_payments as legacy_child')
            ->join('transaction_payments as legacy_parent', 'legacy_parent.id', '=', 'legacy_child.parent_id')
            ->whereIn('legacy_child.parent_id', $parentIds->all())
            ->whereNotNull('legacy_child.transaction_id');

        if ($hasTransactions) {
            $query->leftJoin('transactions as legacy_tx', 'legacy_tx.id', '=', 'legacy_child.transaction_id');
        }
        if ($has('business_id')) {
            $query->where('legacy_child.business_id', $businessId)
                ->where('legacy_parent.business_id', $businessId);
        }
        if ($has('deleted_at')) {
            $query->whereNull('legacy_child.deleted_at')
                ->whereNull('legacy_parent.deleted_at');
        }

        $selectChild = function (string $column, ?string $alias = null) use ($has) {
            $alias = $alias ?: $column;
            return $has($column)
                ? 'legacy_child.' . $column . ' as child_' . $alias
                : DB::raw('NULL as child_' . $alias);
        };
        $selectParent = function (string $column, ?string $alias = null) use ($has) {
            $alias = $alias ?: $column;
            return $has($column)
                ? 'legacy_parent.' . $column . ' as parent_' . $alias
                : DB::raw('NULL as parent_' . $alias);
        };
        $selectTx = function (string $column, ?string $alias = null) use ($hasTransactions) {
            $alias = $alias ?: $column;
            return $hasTransactions && SchemaCache::hasColumn('transactions', $column)
                ? 'legacy_tx.' . $column . ' as tx_' . $alias
                : DB::raw('NULL as tx_' . $alias);
        };

        $groups = $query->select([
                'legacy_child.id as child_id',
                'legacy_child.parent_id as parent_id',
                'legacy_child.transaction_id as child_transaction_id',
                'legacy_child.amount as child_amount',
                $selectChild('paid_on'),
                $selectChild('created_at'),
                $selectChild('payment_for'),
                $selectChild('payment_ref_no'),
                $selectChild('method'),
                $selectChild('cheque_number'),
                $selectChild('cheque_date'),
                $selectChild('bank_name'),
                $selectChild('card_number'),
                $selectChild('card_type'),
                $selectChild('card_holder_name'),
                $selectChild('card_transaction_number'),
                $selectChild('reference_no'),
                $selectChild('transaction_no'),
                $selectChild('transfer_date'),
                $selectChild('note'),
                $selectParent('amount'),
                $selectParent('paid_on'),
                $selectParent('created_at'),
                $selectParent('payment_for'),
                $selectParent('payment_ref_no'),
                $selectParent('method'),
                $selectParent('cheque_number'),
                $selectParent('cheque_date'),
                $selectParent('bank_name'),
                $selectParent('card_number'),
                $selectParent('card_type'),
                $selectParent('card_holder_name'),
                $selectParent('card_transaction_number'),
                $selectParent('reference_no'),
                $selectParent('transaction_no'),
                $selectParent('transfer_date'),
                $selectParent('note'),
                $selectTx('invoice_no'),
                $selectTx('ref_no'),
                $selectTx('type'),
                $selectTx('payment_status'),
                $selectTx('contact_id'),
                $selectTx('customer_ref'),
                $selectTx('sale_ref'),
            ])
            ->orderBy('legacy_child.parent_id')
            ->orderBy('legacy_child.id')
            ->get()
            ->groupBy('parent_id');

        if ($groups->isEmpty()) {
            return $collection;
        }

        $allReplaceIds = collect();
        foreach ($groups as $parentId => $children) {
            $allReplaceIds->push((int) $parentId);
            foreach ($children as $child) {
                $allReplaceIds->push((int) $child->child_id);
            }
        }
        $replaceIds = $allReplaceIds->filter()->unique()->values()->all();

        // Remove only the credit-side payment rows for this logical receipt.
        // Sale/opening-balance debit rows remain untouched.
        $collection = $collection->reject(function ($row) use ($replaceIds) {
            $paymentId = (int) ($row->transaction_payment_id ?? 0);
            if ($paymentId <= 0 || !in_array($paymentId, $replaceIds, true)) {
                return false;
            }
            $type = strtolower(trim((string) ($row->acc_transaction_type ?? $row->type ?? '')));
            return $type === 'credit';
        })->values();

        $replacement = collect();

        foreach ($groups as $parentId => $children) {
            $children = collect($children)->values();
            $first = $children->first();
            if (!$first) {
                continue;
            }

            $parentAmount = abs((float) ($first->parent_amount ?? 0));
            $allocated = (float) $children->sum(fn ($child) => abs((float) ($child->child_amount ?? 0)));
            $residual = max($parentAmount - $allocated, 0.0);

            $parentRef = trim((string) ($first->parent_payment_ref_no ?? ''));
            if ($parentRef === '') {
                $parentRef = 'Bulk-' . (int) $parentId;
            }
            $parentMethod = trim((string) ($first->parent_method ?? ''));
            $parentDate = $first->parent_paid_on ?? $first->parent_created_at ?? null;
            $parentContact = (int) ($first->parent_payment_for ?? 0);

            foreach ($children as $child) {
                $amount = abs((float) ($child->child_amount ?? 0));
                if ($amount < 0.00001) {
                    continue;
                }

                $invoiceNo = trim((string) ($child->tx_invoice_no ?? ''));
                $txRef = trim((string) ($child->tx_ref_no ?? ''));
                $txType = strtolower(trim((string) ($child->tx_type ?? '')));
                if (in_array($txType, ['opening_balance', 'fleet_opening_balance'], true)) {
                    $description = 'Opening Balance';
                } elseif ($invoiceNo !== '') {
                    $description = 'Bill No: ' . $invoiceNo;
                } elseif ($txRef !== '') {
                    $description = 'Bill No: ' . $txRef;
                } else {
                    $description = 'Transaction #' . (int) $child->child_transaction_id;
                }

                $row = new \stdClass();
                $row->id = 'bulk-parent-child-' . (int) $child->child_id;
                $row->created_at = $child->child_created_at ?? $parentDate;
                $row->transaction_date = $child->child_paid_on ?? $parentDate;
                $row->description = $description;
                $row->ledger_note = $child->parent_note ?? $child->child_note ?? '';
                $row->invoice_no = $invoiceNo;
                $row->ref_no = $txRef;
                $row->transaction_type = 'payment';
                $row->type = 'credit';
                $row->payment_status = trim((string) ($child->tx_payment_status ?? '')) ?: 'paid';
                $row->amount = $amount;
                $row->acc_transaction_type = 'credit';
                $row->payment_method = $parentMethod !== '' ? $parentMethod : trim((string) ($child->child_method ?? ''));
                $row->transaction_payment_id = (int) $child->child_id;
                $row->transaction_id = (int) $child->child_transaction_id;
                $row->payment_ref_no = $parentRef;
                $row->paid_in_type = 'customer_bulk';
                $row->contact_id = (int) ($child->tx_contact_id ?? $child->child_payment_for ?? $parentContact);
                $row->customer_name = '';
                $row->customer_code = '';
                $row->cheque_number = $child->parent_cheque_number ?? $child->child_cheque_number ?? '';
                $row->cheque_date = $child->parent_cheque_date ?? $child->child_cheque_date ?? null;
                $row->bank_name = $child->parent_bank_name ?? $child->child_bank_name ?? '';
                $row->transfer_date = $child->parent_transfer_date ?? $child->child_transfer_date ?? null;
                $row->voucher_no = trim((string) ($child->parent_transaction_no ?? '')) !== ''
                    ? $child->parent_transaction_no
                    : ($child->parent_reference_no ?? $child->child_transaction_no ?? $child->child_reference_no ?? '');
                $row->payment_reference_no = $child->parent_reference_no ?? $child->child_reference_no ?? '';
                $row->card_number = $child->parent_card_number ?? $child->child_card_number ?? '';
                $row->card_type = $child->parent_card_type ?? $child->child_card_type ?? '';
                $row->card_holder_name = $child->parent_card_holder_name ?? $child->child_card_holder_name ?? '';
                $row->card_transaction_number = $child->parent_card_transaction_number ?? $child->child_card_transaction_number ?? '';
                $row->payment_note = $child->parent_note ?? $child->child_note ?? '';
                $row->customer_ref = $child->tx_customer_ref ?? '';
                $row->sale_ref = $child->tx_sale_ref ?? '';
                $row->bulk_payment_bills = [];
                $row->is_bulk_payment_allocation = true;
                $row->is_bulk_payment_unallocated = false;
                $row->bulk_lump_sum_amount = $parentAmount;
                $row->bulk_payment_reference = $parentRef;

                $replacement->push($row);
            }

            // The parent amount can be larger than the selected invoice allocations.
            // Preserve only that remainder as a customer advance; never show the full
            // parent receipt as an additional credit.
            if ($residual > 0.00001) {
                $row = new \stdClass();
                $row->id = 'bulk-parent-residual-' . (int) $parentId;
                $row->created_at = $first->parent_created_at ?? $parentDate;
                $row->transaction_date = $parentDate;
                $row->description = 'Unallocated Bulk Payment Advance: ' . $parentRef;
                $row->ledger_note = $first->parent_note ?? '';
                $row->invoice_no = '';
                $row->ref_no = '';
                $row->transaction_type = 'advance_payment';
                $row->type = 'credit';
                $row->payment_status = 'paid';
                $row->amount = $residual;
                $row->acc_transaction_type = 'credit';
                $row->payment_method = $parentMethod;
                $row->transaction_payment_id = (int) $parentId;
                $row->transaction_id = null;
                $row->payment_ref_no = $parentRef;
                $row->paid_in_type = 'customer_bulk';
                $row->contact_id = $parentContact;
                $row->customer_name = '';
                $row->customer_code = '';
                $row->cheque_number = $first->parent_cheque_number ?? '';
                $row->cheque_date = $first->parent_cheque_date ?? null;
                $row->bank_name = $first->parent_bank_name ?? '';
                $row->transfer_date = $first->parent_transfer_date ?? null;
                $row->voucher_no = trim((string) ($first->parent_transaction_no ?? '')) !== ''
                    ? $first->parent_transaction_no
                    : ($first->parent_reference_no ?? '');
                $row->payment_reference_no = $first->parent_reference_no ?? '';
                $row->card_number = $first->parent_card_number ?? '';
                $row->card_type = $first->parent_card_type ?? '';
                $row->card_holder_name = $first->parent_card_holder_name ?? '';
                $row->card_transaction_number = $first->parent_card_transaction_number ?? '';
                $row->payment_note = $first->parent_note ?? '';
                $row->customer_ref = '';
                $row->sale_ref = '';
                $row->bulk_payment_bills = [];
                $row->is_bulk_payment_allocation = false;
                $row->is_bulk_payment_unallocated = true;
                $row->bulk_lump_sum_amount = $parentAmount;
                $row->bulk_payment_reference = $parentRef;

                $replacement->push($row);
            }
        }

        return $collection
            ->concat($replacement)
            ->sortBy(function ($row) {
                $transactionDate = (string) ($row->transaction_date ?? $row->created_at ?? '');
                $createdAt = (string) ($row->created_at ?? $transactionDate);
                $id = (int) preg_replace('/\D+/', '', (string) ($row->id ?? 0));
                return sprintf('%s|%s|%020d', $transactionDate, $createdAt, $id);
            })
            ->values();
    }

    /**
     * Four report cards requested by IS1811.
     */
    public function reportLedgerSummary($rows): array
    {
        $summary = [
            'opening_balance' => 0.0,
            'debit' => 0.0,
            'credit' => 0.0,
            'balance' => 0.0,
            'outstanding_total' => 0.0,
        ];

        foreach (collect($rows) as $row) {
            $amount = abs((float) ($row->amount ?? $row->final_total ?? 0));
            $type = strtolower(trim((string) ($row->acc_transaction_type ?? $row->type ?? 'debit')));
            $description = strtolower((string) ($row->description ?? ''));
            $transactionType = strtolower((string) ($row->transaction_type ?? $row->type ?? ''));
            $isOpening = !empty($row->is_opening_balance)
                || in_array($transactionType, ['opening_balance', 'fleet_opening_balance'], true)
                || str_contains($description, 'opening balance');

            if ($type === 'credit') {
                $summary['credit'] += $amount;
                if ($isOpening) {
                    $summary['opening_balance'] -= $amount;
                }
            } else {
                $summary['debit'] += $amount;
                if ($isOpening) {
                    $summary['opening_balance'] += $amount;
                }
            }
        }

        $summary['balance'] = $summary['debit'] - $summary['credit'];
        $summary['outstanding_total'] = $summary['balance'];

        return $summary;
    }

    protected function customerTransactionTypes(): array
    {
        return [
            'sell',
            'opening_balance',
            'advance_payment',
            'sell_return',
            'settlement',
            'direct_customer_loan',
        ];
    }
}
