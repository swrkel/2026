<?php

namespace Modules\Customers\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Support\SchemaCache;

/**
 * Builds the customer receivable from the two ERP sources that can contain it:
 * contact_ledgers and sales/payment transactions.
 *
 * Existing contact-ledger rows remain authoritative. A transaction or payment
 * row is added only when no matching contact-ledger row exists, preventing
 * duplicate balances while also recovering credit sales that were saved without
 * a contact-ledger posting.
 */
class CustomerReceivableService
{
    private const DEBIT_TRANSACTION_TYPES = [
        'sell',
        'credit_sale',
        'customer_credit_sale',
        'property_sell',
        'route_operation',
        'distribution_sell',
        'distribution_invoice',
        'direct_customer_loan',
        'opening_balance',
        'settlement',
    ];

    private const CREDIT_TRANSACTION_TYPES = [
        'sell_return',
        'advance_payment',
    ];

    /** Security deposits are maintained on the dedicated Security Deposit page. */
    private const EXCLUDED_CUSTOMER_LEDGER_TRANSACTION_TYPES = [
        'security_deposit',
        'refund_security_deposit',
        'security_deposit_refund',
    ];

    private CustomerPaymentDuplicateResolver $duplicateResolver;

    public function __construct(?CustomerPaymentDuplicateResolver $duplicateResolver = null)
    {
        $this->duplicateResolver = $duplicateResolver ?: new CustomerPaymentDuplicateResolver();
    }

    /**
     * Return final receivable balances keyed by customer/contact id.
     *
     * @param array<int, int|string> $customerIds
     * @param array<int|string, float|int|string|null> $openingBalances
     * @return array<int, float>
     */
    public function balancesForCustomers(int $businessId, array $customerIds, array $openingBalances = []): array
    {
        $ids = $this->normaliseIds($customerIds);
        if (empty($ids)) {
            return [];
        }

        $components = $this->emptyComponents($ids);
        $this->mergeComponents($components, $this->ledgerComponents($businessId, $ids));
        $this->mergeComponents($components, $this->missingTransactionComponents($businessId, $ids));
        $this->mergeComponents($components, $this->missingPaymentComponents($businessId, $ids));

        /*
         |----------------------------------------------------------------------
         | S635: the Walk-In Customer's balance is zero, everywhere.
         |----------------------------------------------------------------------
         |
         | The Customer Register still showed a walk-in balance of 98,381,147.45
         | after the walk-in contact_ledgers rows were cleared, because this
         | method does not read contact_ledgers alone - it merges three sources,
         | and the settlement TRANSACTIONS still counted.
         |
         | This is the single place every walk-in balance is produced. The
         | Register list, its overall total, and anything else calling
         | balancesForCustomers() come through here, so zeroing it here keeps them
         | consistent with the ledger, which shows nothing.
         |
         | A walk-in sale is settled at the counter - there is no receivable - so
         | zero is the correct figure, not a cosmetic one.
         |
         | Scoped to contacts.is_default = 1, the flag app/Contact.php already
         | uses. Named customers are untouched.
         */
        $walkInIds = $this->walkInCustomerIds($businessId, $ids);

        $balances = [];
        foreach ($ids as $customerId) {
            if (isset($walkInIds[$customerId])) {
                $balances[$customerId] = 0.0;
                continue;
            }

            $opening = (float) ($openingBalances[$customerId] ?? $openingBalances[(string) $customerId] ?? 0);
            if (!$components[$customerId]['opening_present'] && abs($opening) > 0.0000001) {
                if ($opening >= 0) {
                    $components[$customerId]['debit'] += $opening;
                } else {
                    $components[$customerId]['credit'] += abs($opening);
                }
            }

            $balances[$customerId] = (float) (
                $components[$customerId]['debit'] - $components[$customerId]['credit']
            );
        }

        return $balances;
    }

    /**
     * Fast scalar total for the Customer Register "All Customers Total Due" card.
     *
     * balancesForCustomers() is intentionally customer-by-customer because the
     * register grid needs one result per row.  The overall card only needs one
     * number.  Running the grouped queries for every customer and hydrating all
     * of those component rows made this small card one of the slowest requests
     * on older/high-volume tenant databases.
     *
     * This method keeps the exact same receivable rules (canonical ledger rows,
     * missing sales, canonical parent receipts, security-deposit exclusion,
     * opening balance fallback and Walk-In = zero) but performs scalar SUMs and
     * only loads the small customer master list.  No accounting rows are changed.
     */
    public function overallBalanceForBusiness(int $businessId): float
    {
        if (!SchemaCache::hasTable('contacts') || !SchemaCache::hasColumn('contacts', 'id')) {
            return 0.0;
        }

        $customerQuery = DB::table('contacts')
            ->where('business_id', $businessId);

        if (SchemaCache::hasColumn('contacts', 'type')) {
            $customerQuery->whereIn('type', ['customer', 'both']);
        }
        if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
            $customerQuery->whereNull('deleted_at');
        }
        // S635: Walk-In Customer never carries a receivable.
        if (SchemaCache::hasColumn('contacts', 'is_default')) {
            $customerQuery->where(function (Builder $walkIn) {
                $walkIn->whereNull('is_default')->orWhere('is_default', '!=', 1);
            });
        }

        $columns = ['id'];
        if (SchemaCache::hasColumn('contacts', 'opening_balance')) {
            $columns[] = 'opening_balance';
        }

        $customers = $customerQuery->select($columns)->orderBy('id')->get();
        if ($customers->isEmpty()) {
            return 0.0;
        }

        $customerIds = $customers->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $total = 0.0;

        /* 1) Canonical contact-ledger balance. */
        if ($this->canUseContactLedgers()) {
            $ledger = DB::table('contact_ledgers as cl')
                ->where('cl.business_id', $businessId)
                ->whereIn('cl.contact_id', $customerIds);

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $ledger->whereNull('cl.deleted_at');
            }

            $this->duplicateResolver->applyCanonicalLedgerPostingFilter($ledger, 'cl');

            $hasTransactionJoin = SchemaCache::hasColumn('contact_ledgers', 'transaction_id')
                && $this->canUseTransactions();
            if ($hasTransactionJoin) {
                $ledger->leftJoin('transactions as ledger_tx', 'cl.transaction_id', '=', 'ledger_tx.id');
            }

            $hasPaymentJoin = SchemaCache::hasColumn('contact_ledgers', 'transaction_payment_id')
                && $this->canUseTransactionPayments();
            if ($hasPaymentJoin) {
                $ledger->leftJoin('transaction_payments as ledger_tp', 'cl.transaction_payment_id', '=', 'ledger_tp.id');
            }

            $this->applySecurityDepositLedgerExclusion(
                $ledger,
                $hasTransactionJoin ? 'ledger_tx' : null,
                $hasPaymentJoin ? 'ledger_tp' : null
            );

            $type = $this->ledgerTypeExpression('cl');
            $amount = SchemaCache::hasColumn('contact_ledgers', 'amount')
                ? 'ABS(COALESCE(cl.amount, 0))'
                : '0';

            $ledgerTotal = $ledger
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN {$type} = 'credit' THEN -({$amount}) ELSE ({$amount}) END), 0) AS net_total"
                )
                ->first();

            $total += (float) ($ledgerTotal->net_total ?? 0);
        }

        /* 2) Valid customer transactions that have no contact-ledger posting. */
        if ($this->canUseTransactions()) {
            $transactions = DB::table('transactions as tx')
                ->where('tx.business_id', $businessId)
                ->whereIn('tx.contact_id', $customerIds);

            $this->applyTransactionScope($transactions, 'tx');
            $this->applyMissingTransactionLedgerFilter($transactions, 'tx');

            $net = $this->transactionNetExpression('tx');
            $transactionTotal = $transactions
                ->selectRaw("COALESCE(SUM({$net}), 0) AS net_total")
                ->first();

            $total += (float) ($transactionTotal->net_total ?? 0);
        }

        /* 3) Actual customer receipts not already represented in contact_ledgers. */
        if ($this->canUseTransactionPayments() && $this->canUseTransactions()) {
            $paymentNet = $this->paymentNetExpression('tp');

            // Invoice/transaction-linked receipts.
            $transactionLinked = DB::table('transaction_payments as tp')
                ->join('transactions as tx', 'tp.transaction_id', '=', 'tx.id')
                ->where('tx.business_id', $businessId)
                ->whereIn('tx.contact_id', $customerIds);

            if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                $transactionLinked->where('tp.business_id', $businessId);
            }
            if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                $transactionLinked->whereNull('tp.deleted_at');
            }

            $this->applyTransactionScope($transactionLinked, 'tx');
            $this->applySecurityDepositPaymentExclusion($transactionLinked, 'tp');
            $this->applyCanonicalReceiptComponentFilter($transactionLinked, 'tp');
            $this->applyFastMissingPaymentLedgerFilterForComponents(
                $transactionLinked,
                'tp',
                'tx',
                $businessId,
                $customerIds,
                false
            );

            $linkedTotal = $transactionLinked
                ->selectRaw("COALESCE(SUM({$paymentNet}), 0) AS net_total")
                ->first();
            $total += (float) ($linkedTotal->net_total ?? 0);

            // Balance/direct receipts: parent receipt is the money; child rows are
            // invoice allocations and must not be counted again.
            if (SchemaCache::hasColumn('transaction_payments', 'payment_for')) {
                $direct = DB::table('transaction_payments as tp')
                    ->leftJoin('transactions as tx', 'tp.transaction_id', '=', 'tx.id')
                    ->whereIn('tp.payment_for', $customerIds)
                    ->whereNull('tx.contact_id')
                    ->where(function (Builder $scope) use ($businessId) {
                        $scope->where('tx.business_id', $businessId)
                            ->orWhereNull('tx.id');
                    });

                if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                    $direct->where('tp.business_id', $businessId);
                }
                if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                    $direct->whereNull('tp.deleted_at');
                }

                $this->applyTransactionScope($direct, 'tx', true);
                $this->applySecurityDepositPaymentExclusion($direct, 'tp');
                $this->applyCanonicalReceiptComponentFilter($direct, 'tp');
                $this->applyFastMissingPaymentLedgerFilterForComponents(
                    $direct,
                    'tp',
                    'tx',
                    $businessId,
                    $customerIds,
                    true
                );

                $directTotal = $direct
                    ->selectRaw("COALESCE(SUM({$paymentNet}), 0) AS net_total")
                    ->first();
                $total += (float) ($directTotal->net_total ?? 0);
            }
        }

        /*
         * 4) contacts.opening_balance is a fallback only.  If an opening balance
         * already exists as a transaction or an opening ledger posting, adding
         * the Contacts value again would duplicate it.
         */
        if (SchemaCache::hasColumn('contacts', 'opening_balance')) {
            $openingPresent = [];

            if ($this->canUseTransactions()) {
                $openingTx = DB::table('transactions as opening_tx')
                    ->where('opening_tx.business_id', $businessId)
                    ->whereIn('opening_tx.contact_id', $customerIds)
                    ->where('opening_tx.type', 'opening_balance');

                $this->applyTransactionScope($openingTx, 'opening_tx');

                foreach ($openingTx->distinct()->pluck('opening_tx.contact_id') as $id) {
                    $openingPresent[(int) $id] = true;
                }
            }

            if ($this->canUseContactLedgers()) {
                if (SchemaCache::hasColumn('contact_ledgers', 'description')) {
                    $openingLedger = DB::table('contact_ledgers as opening_cl')
                        ->where('opening_cl.business_id', $businessId)
                        ->whereIn('opening_cl.contact_id', $customerIds)
                        ->whereRaw("LOWER(COALESCE(opening_cl.description, '')) LIKE '%opening%'");

                    if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                        $openingLedger->whereNull('opening_cl.deleted_at');
                    }

                    foreach ($openingLedger->distinct()->pluck('opening_cl.contact_id') as $id) {
                        $openingPresent[(int) $id] = true;
                    }
                }

                if (SchemaCache::hasColumn('contact_ledgers', 'transaction_id') && $this->canUseTransactions()) {
                    $openingLedgerTx = DB::table('contact_ledgers as opening_cl')
                        ->join('transactions as opening_linked_tx', 'opening_linked_tx.id', '=', 'opening_cl.transaction_id')
                        ->where('opening_cl.business_id', $businessId)
                        ->whereIn('opening_cl.contact_id', $customerIds)
                        ->where('opening_linked_tx.type', 'opening_balance');

                    if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                        $openingLedgerTx->whereNull('opening_cl.deleted_at');
                    }

                    foreach ($openingLedgerTx->distinct()->pluck('opening_cl.contact_id') as $id) {
                        $openingPresent[(int) $id] = true;
                    }
                }
            }

            foreach ($customers as $customer) {
                $id = (int) $customer->id;
                if (!isset($openingPresent[$id])) {
                    $total += (float) ($customer->opening_balance ?? 0);
                }
            }
        }

        return (float) $total;
    }

    /**
     * Return debit, credit and balance totals for one customer without hydrating
     * the complete ledger row history.
     */
    public function summaryForCustomer(
        int $businessId,
        int $customerId,
        ?Customer $customer = null
    ): array {
        $opening = $this->openingBalance($businessId, $customerId, $customer);
        $components = $this->emptyComponents([$customerId]);

        $this->mergeComponents($components, $this->ledgerComponents($businessId, [$customerId]));
        $this->mergeComponents($components, $this->missingTransactionComponents($businessId, [$customerId]));
        $this->mergeComponents($components, $this->missingPaymentComponents($businessId, [$customerId]));

        if (!$components[$customerId]['opening_present'] && abs($opening) > 0.0000001) {
            if ($opening >= 0) {
                $components[$customerId]['debit'] += $opening;
            } else {
                $components[$customerId]['credit'] += abs($opening);
            }
        }

        $debit = (float) $components[$customerId]['debit'];
        $credit = (float) $components[$customerId]['credit'];

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $debit - $credit,
        ];
    }

    /**
     * Rows that are absent from contact_ledgers but already exist in the sales
     * and payment tables. These rows are merged into the action Ledger page.
     */
    public function missingRowsForCustomer(
        int $businessId,
        int $customerId,
        int $limit = 500,
        bool $includeCustomerColumns = true,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        $limit = min(max($limit, 1), 5000);
        $rows = collect();

        foreach ($this->missingTransactionRows($businessId, $customerId, $limit, $includeCustomerColumns, $startDate, $endDate) as $row) {
            $rows->push($row);
        }

        foreach ($this->missingPaymentRows($businessId, $customerId, $limit, $includeCustomerColumns, $startDate, $endDate) as $row) {
            $rows->push($row);
        }

        return $rows
            ->sortBy(function ($row) {
                return sprintf(
                    '%s|%020d',
                    (string) ($row->transaction_date ?? $row->created_at ?? ''),
                    (int) preg_replace('/\D+/', '', (string) ($row->id ?? 0))
                );
            })
            ->values()
            ->take($limit);
    }

    /** @return array<int, array{debit: float, credit: float, opening_present: bool}> */
    private function ledgerComponents(int $businessId, array $customerIds): array
    {
        if (!$this->canUseContactLedgers()) {
            return [];
        }

        $table = 'contact_ledgers';
        $query = DB::table($table . ' as cl')
            ->where('cl.business_id', $businessId)
            ->whereIn('cl.contact_id', $customerIds);

        if (SchemaCache::hasColumn($table, 'deleted_at')) {
            $query->whereNull('cl.deleted_at');
        }

        // IS2276: the receivable summary must use the same canonical physical
        // ledger rows as the visible Customer Ledger. Otherwise the page can
        // show one payment but Total Credit / Balance Due still count it two or
        // three times from duplicated contact_ledgers records.
        $this->duplicateResolver->applyCanonicalLedgerPostingFilter($query, 'cl');

        $hasTransactionJoin = SchemaCache::hasColumn($table, 'transaction_id')
            && $this->canUseTransactions();
        if ($hasTransactionJoin) {
            $query->leftJoin('transactions as ledger_tx', 'cl.transaction_id', '=', 'ledger_tx.id');
        }

        $hasPaymentJoin = SchemaCache::hasColumn($table, 'transaction_payment_id')
            && $this->canUseTransactionPayments();
        if ($hasPaymentJoin) {
            $query->leftJoin('transaction_payments as ledger_tp', 'cl.transaction_payment_id', '=', 'ledger_tp.id');
        }

        $this->applySecurityDepositLedgerExclusion(
            $query,
            $hasTransactionJoin ? 'ledger_tx' : null,
            $hasPaymentJoin ? 'ledger_tp' : null
        );

        $type = $this->ledgerTypeExpression('cl');
        $amount = SchemaCache::hasColumn($table, 'amount')
            ? 'ABS(COALESCE(cl.amount, 0))'
            : '0';
        $opening = $this->ledgerOpeningExpression('cl', $hasTransactionJoin ? 'ledger_tx' : null);

        $rows = $query
            ->selectRaw(
                "cl.contact_id, " .
                "COALESCE(SUM(CASE WHEN {$type} = 'credit' THEN 0 ELSE {$amount} END), 0) AS debit_total, " .
                "COALESCE(SUM(CASE WHEN {$type} = 'credit' THEN {$amount} ELSE 0 END), 0) AS credit_total, " .
                "MAX(CASE WHEN {$opening} THEN 1 ELSE 0 END) AS opening_present"
            )
            ->groupBy('cl.contact_id')
            ->get();

        return $this->componentRowsToArray($rows);
    }

    /** @return array<int, array{debit: float, credit: float, opening_present: bool}> */
    private function missingTransactionComponents(int $businessId, array $customerIds): array
    {
        if (!$this->canUseTransactions()) {
            return [];
        }

        $query = DB::table('transactions as tx')
            ->where('tx.business_id', $businessId)
            ->whereIn('tx.contact_id', $customerIds);

        $this->applyTransactionScope($query, 'tx');
        $this->applyMissingTransactionLedgerFilter($query, 'tx');

        $net = $this->transactionNetExpression('tx');
        $rows = $query
            ->selectRaw(
                "tx.contact_id, " .
                "COALESCE(SUM(CASE WHEN ({$net}) >= 0 THEN ({$net}) ELSE 0 END), 0) AS debit_total, " .
                "COALESCE(SUM(CASE WHEN ({$net}) < 0 THEN -({$net}) ELSE 0 END), 0) AS credit_total, " .
                "MAX(CASE WHEN LOWER(COALESCE(tx.type, '')) = 'opening_balance' THEN 1 ELSE 0 END) AS opening_present"
            )
            ->groupBy('tx.contact_id')
            ->get();

        return $this->componentRowsToArray($rows);
    }

    /** @return array<int, array{debit: float, credit: float, opening_present: bool}> */
    private function missingPaymentComponents(int $businessId, array $customerIds): array
    {
        if (!$this->canUseTransactionPayments() || !$this->canUseTransactions()) {
            return [];
        }

        $customerIds = array_values(array_filter(array_map('intval', $customerIds)));
        if (empty($customerIds)) {
            return [];
        }

        /*
         |------------------------------------------------------------------
         | Keep the two customer-link paths indexable on large tenants.
         |------------------------------------------------------------------
         |
         | A single COALESCE(tx.contact_id, tp.payment_for) predicate forces
         | MariaDB to inspect a large portion of transaction_payments.  The two
         | paths are mutually exclusive for balance purposes, so calculate them
         | separately and let the existing indexes do their job.
         */
        $components = $this->emptyComponents($customerIds);
        $net = $this->paymentNetExpression('tp');

        // Path 1: normal payment linked through a transaction customer.
        $transactionLinked = DB::table('transaction_payments as tp')
            ->join('transactions as tx', 'tp.transaction_id', '=', 'tx.id')
            ->where('tx.business_id', $businessId)
            ->whereIn('tx.contact_id', $customerIds);

        if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
            $transactionLinked->where('tp.business_id', $businessId);
        }
        if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            $transactionLinked->whereNull('tp.deleted_at');
        }

        $this->applyTransactionScope($transactionLinked, 'tx');
        $this->applySecurityDepositPaymentExclusion($transactionLinked, 'tp');
        $this->applyCanonicalReceiptComponentFilter($transactionLinked, 'tp');
        $this->applyFastMissingPaymentLedgerFilterForComponents(
            $transactionLinked,
            'tp',
            'tx',
            $businessId,
            $customerIds,
            false
        );

        $transactionRows = $transactionLinked
            ->selectRaw(
                "tx.contact_id as contact_id, " .
                "COALESCE(SUM(CASE WHEN ({$net}) >= 0 THEN ({$net}) ELSE 0 END), 0) AS debit_total, " .
                "COALESCE(SUM(CASE WHEN ({$net}) < 0 THEN -({$net}) ELSE 0 END), 0) AS credit_total, " .
                "0 AS opening_present"
            )
            ->groupBy('tx.contact_id')
            ->get();

        $this->mergeComponents($components, $this->componentRowsToArray($transactionRows));

        // Path 2: direct/customer-balance payment linked through payment_for.
        if (SchemaCache::hasColumn('transaction_payments', 'payment_for')) {
            $direct = DB::table('transaction_payments as tp')
                ->leftJoin('transactions as tx', 'tp.transaction_id', '=', 'tx.id')
                ->whereIn('tp.payment_for', $customerIds)
                ->whereNull('tx.contact_id')
                ->where(function (Builder $scope) use ($businessId) {
                    $scope->where('tx.business_id', $businessId)
                        ->orWhereNull('tx.id');
                });

            if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                $direct->where('tp.business_id', $businessId);
            }
            if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                $direct->whereNull('tp.deleted_at');
            }

            $this->applyTransactionScope($direct, 'tx', true);
            $this->applySecurityDepositPaymentExclusion($direct, 'tp');
            $this->applyCanonicalReceiptComponentFilter($direct, 'tp');
            $this->applyFastMissingPaymentLedgerFilterForComponents(
                $direct,
                'tp',
                'tx',
                $businessId,
                $customerIds,
                true
            );

            $directRows = $direct
                ->selectRaw(
                    "tp.payment_for as contact_id, " .
                    "COALESCE(SUM(CASE WHEN ({$net}) >= 0 THEN ({$net}) ELSE 0 END), 0) AS debit_total, " .
                    "COALESCE(SUM(CASE WHEN ({$net}) < 0 THEN -({$net}) ELSE 0 END), 0) AS credit_total, " .
                    "0 AS opening_present"
                )
                ->groupBy('tp.payment_for')
                ->get();

            $this->mergeComponents($components, $this->componentRowsToArray($directRows));
        }

        return $components;
    }

    private function missingTransactionRows(
        int $businessId,
        int $customerId,
        int $limit,
        bool $includeCustomerColumns,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        if (!$this->canUseTransactions()) {
            return collect();
        }

        $query = DB::table('transactions as tx');
        if ($includeCustomerColumns && SchemaCache::hasTable('contacts')) {
            $query->leftJoin('contacts as customer', 'tx.contact_id', '=', 'customer.id');
        }

        $query->where('tx.business_id', $businessId)
            ->where('tx.contact_id', $customerId);

        $this->applyTransactionScope($query, 'tx');
        $this->applyMissingTransactionLedgerFilter($query, 'tx');

        $net = $this->transactionNetExpression('tx');
        $date = SchemaCache::hasColumn('transactions', 'transaction_date')
            ? 'tx.transaction_date'
            : (SchemaCache::hasColumn('transactions', 'created_at') ? 'tx.created_at' : 'NULL');
        $created = SchemaCache::hasColumn('transactions', 'created_at') ? 'tx.created_at' : $date;
        $dateColumn = SchemaCache::hasColumn('transactions', 'transaction_date')
            ? 'tx.transaction_date'
            : (SchemaCache::hasColumn('transactions', 'created_at') ? 'tx.created_at' : null);
        if ($dateColumn && $startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        }
        if ($dateColumn && $endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }
        $invoice = SchemaCache::hasColumn('transactions', 'invoice_no') ? "COALESCE(tx.invoice_no, '')" : "''";
        $reference = SchemaCache::hasColumn('transactions', 'ref_no') ? "COALESCE(tx.ref_no, '')" : "''";
        // MA-005: label the reference the same way CustomerLedgerService does,
        // so a row that reaches the ledger through this fallback path reads
        // identically to one built from contact_ledgers. The Bill/Settlement
        // wording is driven by transactions.is_settlement, guarded with
        // hasColumn for installs that predate that column.
        $billLabel = SchemaCache::hasColumn('transactions', 'is_settlement')
            ? "CASE WHEN tx.is_settlement = 1 THEN 'Settlement No: ' ELSE 'Bill No: ' END"
            : "'Bill No: '";
        $description = "COALESCE("
            . "CONCAT({$billLabel}, NULLIF({$invoice}, '')), "
            . "CONCAT({$billLabel}, NULLIF({$reference}, '')), "
            . "'Credit Sale')";
        $paymentStatus = SchemaCache::hasColumn('transactions', 'payment_status')
            ? "COALESCE(tx.payment_status, '')"
            : "''";
        $customerName = $includeCustomerColumns && SchemaCache::hasTable('contacts')
            ? "COALESCE(customer.name, '')"
            : "''";
        $customerCode = $includeCustomerColumns && SchemaCache::hasTable('contacts')
            ? "COALESCE(customer.contact_id, '')"
            : "''";

        return $query
            ->selectRaw(
                "CONCAT('transaction-', tx.id) AS id, " .
                "{$created} AS created_at, {$date} AS transaction_date, " .
                "{$description} AS description, {$invoice} AS invoice_no, {$reference} AS ref_no, " .
                "tx.type AS transaction_type, " .
                "CASE WHEN ({$net}) < 0 THEN 'credit' ELSE 'debit' END AS type, " .
                "{$paymentStatus} AS payment_status, ABS(COALESCE(tx.final_total, 0)) AS amount, " .
                "CASE WHEN ({$net}) < 0 THEN 'credit' ELSE 'debit' END AS acc_transaction_type, " .
                "'' AS payment_method, tx.contact_id, {$customerName} AS customer_name, {$customerCode} AS customer_code"
            )
            ->orderByRaw($date . ' ASC')
            ->orderBy('tx.id')
            ->limit($limit)
            ->get();
    }

    private function missingPaymentRows(
        int $businessId,
        int $customerId,
        int $limit,
        bool $includeCustomerColumns,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        if (!$this->canUseTransactionPayments() || !$this->canUseTransactions()) {
            return collect();
        }

        /*
         |----------------------------------------------------------------------
         | S641: a LEFT join, so balance payments are not dropped.
         |----------------------------------------------------------------------
         |
         | This was an inner join on tp.transaction_id. A payment taken against
         | the customer's BALANCE rather than one invoice records the customer in
         | `payment_for` and leaves transaction_id NULL, so an inner join matched
         | nothing and those credits never reached the ledger - the same fault
         | fixed in LA-1146 for the payment listings.
         |
         | The customer is now matched from the transaction when there is one and
         | from payment_for when there is not, which is what makes a walk-in
         | ledger able to reach zero.
         */
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as tx', 'tp.transaction_id', '=', 'tx.id');

        if ($includeCustomerColumns && SchemaCache::hasTable('contacts')) {
            $query->leftJoin('contacts as customer', function ($join) {
                $join->on('customer.id', '=', DB::raw('COALESCE(tx.contact_id, tp.payment_for)'));
            });
        }

        $query->where(function ($scope) use ($businessId, $customerId) {
            $scope->where(function ($viaTransaction) use ($businessId, $customerId) {
                $viaTransaction->where('tx.business_id', $businessId)
                    ->where('tx.contact_id', $customerId);
            });

            if (SchemaCache::hasColumn('transaction_payments', 'payment_for')) {
                $scope->orWhere(function ($viaPaymentFor) use ($customerId) {
                    $viaPaymentFor->whereNull('tp.transaction_id')
                        ->where('tp.payment_for', $customerId);
                });
            }
        });

        if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
            $query->where('tp.business_id', $businessId);
        }
        if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('tp.deleted_at');
        }

        $this->applyTransactionScope($query, 'tx', true);
        $this->applySecurityDepositPaymentExclusion($query, 'tp');
        $this->applyMissingPaymentLedgerFilter($query, 'tp', 'tx');

        $net = $this->paymentNetExpression('tp');
        $paidOn = SchemaCache::hasColumn('transaction_payments', 'paid_on')
            ? 'tp.paid_on'
            : (SchemaCache::hasColumn('transaction_payments', 'created_at') ? 'tp.created_at' : 'NULL');
        $created = SchemaCache::hasColumn('transaction_payments', 'created_at') ? 'tp.created_at' : $paidOn;
        $paidDateColumn = SchemaCache::hasColumn('transaction_payments', 'paid_on')
            ? 'tp.paid_on'
            : (SchemaCache::hasColumn('transaction_payments', 'created_at') ? 'tp.created_at' : null);
        if ($paidDateColumn && $startDate) {
            $query->whereDate($paidDateColumn, '>=', $startDate);
        }
        if ($paidDateColumn && $endDate) {
            $query->whereDate($paidDateColumn, '<=', $endDate);
        }
        $paymentReference = SchemaCache::hasColumn('transaction_payments', 'payment_ref_no')
            ? "COALESCE(tp.payment_ref_no, '')"
            : "''";
        $method = SchemaCache::hasColumn('transaction_payments', 'method')
            ? "COALESCE(tp.method, '')"
            : "''";
        $paidInType = SchemaCache::hasColumn('transaction_payments', 'paid_in_type')
            ? "COALESCE(tp.paid_in_type, '')"
            : "''";
        $chequeNumber = SchemaCache::hasColumn('transaction_payments', 'cheque_number')
            ? "COALESCE(tp.cheque_number, '')"
            : "''";
        $bankName = SchemaCache::hasColumn('transaction_payments', 'bank_name')
            ? "COALESCE(tp.bank_name, '')"
            : "''";
        $paymentContact = SchemaCache::hasColumn('transaction_payments', 'payment_for')
            ? "COALESCE(tx.contact_id, tp.payment_for)"
            : "tx.contact_id";
        $invoice = SchemaCache::hasColumn('transactions', 'invoice_no') ? "COALESCE(tx.invoice_no, '')" : "''";
        $reference = SchemaCache::hasColumn('transactions', 'ref_no') ? "COALESCE(tx.ref_no, '')" : "''";
        $paymentStatus = SchemaCache::hasColumn('transactions', 'payment_status')
            ? "COALESCE(tx.payment_status, 'paid')"
            : "'paid'";
        $customerName = $includeCustomerColumns && SchemaCache::hasTable('contacts')
            ? "COALESCE(customer.name, '')"
            : "''";
        $customerCode = $includeCustomerColumns && SchemaCache::hasTable('contacts')
            ? "COALESCE(customer.contact_id, '')"
            : "''";

        return $query
            ->selectRaw(
                "CONCAT('payment-', tp.id) AS id, {$created} AS created_at, {$paidOn} AS transaction_date, " .
                // MA-005: matches the labelled format used by CustomerLedgerService.
                "COALESCE(CONCAT('Payment Ref: ', NULLIF({$paymentReference}, '')), CONCAT('Payment #', tp.id)) AS description, " .
                "{$invoice} AS invoice_no, {$reference} AS ref_no, 'payment' AS transaction_type, " .
                "CASE WHEN ({$net}) < 0 THEN 'credit' ELSE 'debit' END AS type, " .
                "{$paymentStatus} AS payment_status, ABS(COALESCE(tp.amount, 0)) AS amount, " .
                "CASE WHEN ({$net}) < 0 THEN 'credit' ELSE 'debit' END AS acc_transaction_type, " .
                "{$method} AS payment_method, {$paymentReference} AS payment_ref_no, {$paidInType} AS paid_in_type, " .
                "{$chequeNumber} AS cheque_number, {$bankName} AS bank_name, " .
                "tp.id AS transaction_payment_id, tx.id AS transaction_id, {$paymentContact} AS contact_id, " .
                "{$customerName} AS customer_name, {$customerCode} AS customer_code"
            )
            ->orderByRaw($paidOn . ' ASC')
            ->orderBy('tp.id')
            ->limit($limit)
            ->get();
    }

    private function applyTransactionScope(
        Builder $query,
        string $alias,
        bool $allowNullTransaction = false
    ): void {
        $types = array_values(array_unique(array_merge(
            self::DEBIT_TRANSACTION_TYPES,
            self::CREDIT_TRANSACTION_TYPES
        )));

        $query->where(function (Builder $scope) use ($alias, $types, $allowNullTransaction) {
            $scope->whereIn($alias . '.type', $types);

            if (SchemaCache::hasColumn('transactions', 'is_credit_sale')) {
                $scope->orWhere($alias . '.is_credit_sale', 1);
            }

            /*
             * S641: with the LEFT join in missingPaymentRows, a payment made
             * against the customer balance has no transaction, so tx.type is
             * NULL and this whereIn would drop it - undoing the LEFT join.
             * There is no transaction type to vet; the payment is already
             * matched to the customer through payment_for.
             */
            if ($allowNullTransaction) {
                $scope->orWhereNull($alias . '.id');
            }
        });

        // Security deposits (and their refunds) are deliberately kept out of
        // Customer Ledger / Total Due. They have their own Security Deposit page.
        $query->where(function (Builder $depositScope) use ($alias, $allowNullTransaction) {
            if ($allowNullTransaction) {
                $depositScope->whereNull($alias . '.id')
                    ->orWhereNotIn($alias . '.type', self::EXCLUDED_CUSTOMER_LEDGER_TRANSACTION_TYPES);
            } else {
                $depositScope->whereNotIn($alias . '.type', self::EXCLUDED_CUSTOMER_LEDGER_TRANSACTION_TYPES);
            }
        });

        if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }

        if (SchemaCache::hasColumn('transactions', 'status')) {
            $query->where(function (Builder $status) use ($alias) {
                $status->whereNull($alias . '.status')
                    ->orWhereNotIn($alias . '.status', [
                        'draft',
                        'quotation',
                        'ordered',
                        'cancelled',
                        'canceled',
                        'void',
                    ]);
            });
        }
    }

    /**
     * Bulk/customer-balance receipts can be stored as one parent receipt plus
     * several child rows that allocate that same money to individual invoices.
     * For receivable TOTALS the money must be counted once only: the parent is
     * the actual receipt; children are allocation detail for the ledger UI.
     *
     * Keep normal parentless payments. Keep an orphan child only when its parent
     * no longer exists, so historical/legacy rows do not lose a genuine credit.
     */
    private function applyCanonicalReceiptComponentFilter(Builder $query, string $paymentAlias): void
    {
        if (!SchemaCache::hasColumn('transaction_payments', 'parent_id')) {
            return;
        }

        $query->where(function (Builder $receipt) use ($paymentAlias) {
            $receipt->whereNull($paymentAlias . '.parent_id')
                ->orWhere($paymentAlias . '.parent_id', 0)
                ->orWhereNotExists(function (Builder $parent) use ($paymentAlias) {
                    $parent->selectRaw('1')
                        ->from('transaction_payments as canonical_receipt_parent')
                        ->whereColumn('canonical_receipt_parent.id', $paymentAlias . '.parent_id');

                    if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                        $parent->whereColumn(
                            'canonical_receipt_parent.business_id',
                            $paymentAlias . '.business_id'
                        );
                    }

                    if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                        $parent->whereNull('canonical_receipt_parent.deleted_at');
                    }
                });
        });
    }

    /**
     * Exclude direct security-deposit payment rows that have no transaction.
     * Current Customers security deposits use a durable CUS-SECURITY-DEPOSIT-
     * payment reference, while legacy deposits are excluded by transaction type.
     */
    private function applySecurityDepositPaymentExclusion(Builder $query, string $paymentAlias): void
    {
        if (SchemaCache::hasColumn('transaction_payments', 'payment_ref_no')) {
            $query->where(function (Builder $depositPayment) use ($paymentAlias) {
                $depositPayment->whereNull($paymentAlias . '.payment_ref_no')
                    ->orWhere($paymentAlias . '.payment_ref_no', '')
                    ->orWhere($paymentAlias . '.payment_ref_no', 'not like', 'CUS-SECURITY-DEPOSIT-%');
            });
        }

        if (SchemaCache::hasColumn('transaction_payments', 'paid_in_type')) {
            $query->where(function (Builder $depositPaymentType) use ($paymentAlias) {
                $depositPaymentType->whereNull($paymentAlias . '.paid_in_type')
                    ->orWhereNotIn($paymentAlias . '.paid_in_type', ['security_deposit', 'security deposit']);
            });
        }
    }

    /**
     * Exclude security-deposit mirror rows already written to contact_ledgers.
     */
    private function applySecurityDepositLedgerExclusion(
        Builder $query,
        ?string $transactionAlias,
        ?string $paymentAlias
    ): void {
        if ($transactionAlias !== null) {
            $query->where(function (Builder $depositTransaction) use ($transactionAlias) {
                $depositTransaction->whereNull($transactionAlias . '.id')
                    ->orWhereNull($transactionAlias . '.type')
                    ->orWhereNotIn($transactionAlias . '.type', self::EXCLUDED_CUSTOMER_LEDGER_TRANSACTION_TYPES);
            });
        }

        if ($paymentAlias !== null) {
            $this->applySecurityDepositPaymentExclusion($query, $paymentAlias);
        }
    }

    private function applyMissingTransactionLedgerFilter(Builder $query, string $transactionAlias): void
    {
        if (!$this->canUseContactLedgers()) {
            return;
        }

        if (SchemaCache::hasColumn('contact_ledgers', 'transaction_id')) {
            $query->whereNotExists(function (Builder $ledger) use ($transactionAlias) {
                $ledger->selectRaw('1')
                    ->from('contact_ledgers as represented_ledger')
                    ->whereColumn('represented_ledger.business_id', $transactionAlias . '.business_id')
                    ->whereColumn('represented_ledger.contact_id', $transactionAlias . '.contact_id')
                    ->whereColumn('represented_ledger.transaction_id', $transactionAlias . '.id');

                if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                    $ledger->whereNull('represented_ledger.deleted_at');
                }
            });

            return;
        }

        // Legacy fallback: when transaction_id does not exist, supplement only a
        // customer that has no ledger rows at all. This avoids duplicate balances.
        $query->whereNotExists(function (Builder $ledger) use ($transactionAlias) {
            $ledger->selectRaw('1')
                ->from('contact_ledgers as any_customer_ledger')
                ->whereColumn('any_customer_ledger.business_id', $transactionAlias . '.business_id')
                ->whereColumn('any_customer_ledger.contact_id', $transactionAlias . '.contact_id');

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $ledger->whereNull('any_customer_ledger.deleted_at');
            }
        });
    }

    /**
     * Fast set-based exclusion used only by the aggregate receivable calculation.
     *
     * The older correlated NOT EXISTS branch is intentionally retained for the
     * row-level Customer Ledger builder, where it runs for one customer at a time.
     * Register/Dashboard totals can span every customer and tens of thousands of
     * payment rows, so correlated OR conditions become very expensive on MariaDB.
     *
     * This aggregate path preserves the same three "already represented" rules:
     *  1. exact transaction_payment_id,
     *  2. parent_id linked to a represented payment,
     *  3. another allocation with the same payment_ref_no as the represented
     *     anchor payment (Bulk Payment compatibility).
     *
     * Each rule is expressed as a set-based NOT IN subquery so the existing
     * business/contact/payment indexes remain usable.
     */
    private function applyFastMissingPaymentLedgerFilterForComponents(
        Builder $query,
        string $paymentAlias,
        string $transactionAlias,
        int $businessId,
        array $customerIds,
        bool $directPaymentFor
    ): void {
        if (!$this->canUseContactLedgers()
            || !SchemaCache::hasColumn('contact_ledgers', 'transaction_payment_id')) {
            $businessExpression = $directPaymentFor && SchemaCache::hasColumn('transaction_payments', 'business_id')
                ? $paymentAlias . '.business_id'
                : $transactionAlias . '.business_id';
            $contactExpression = $directPaymentFor
                ? $paymentAlias . '.payment_for'
                : $transactionAlias . '.contact_id';

            $this->applyMissingPaymentLedgerFilter(
                $query,
                $paymentAlias,
                $transactionAlias,
                $businessExpression,
                $contactExpression
            );

            return;
        }

        $customerIds = array_values(array_filter(array_map('intval', $customerIds)));
        if (empty($customerIds)) {
            return;
        }

        // Exact payment IDs already represented in contact_ledgers.
        $representedPayments = DB::table('contact_ledgers as represented_component_ledger')
            ->select('represented_component_ledger.transaction_payment_id')
            ->where('represented_component_ledger.business_id', $businessId)
            ->whereIn('represented_component_ledger.contact_id', $customerIds)
            ->whereNotNull('represented_component_ledger.transaction_payment_id')
            ->where('represented_component_ledger.transaction_payment_id', '>', 0);

        if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
            $representedPayments->whereNull('represented_component_ledger.deleted_at');
        }

        $query->whereNotIn($paymentAlias . '.id', $representedPayments);

        // Child allocation/payment whose parent payment is already represented.
        if (SchemaCache::hasColumn('transaction_payments', 'parent_id')) {
            $representedParents = DB::table('contact_ledgers as represented_parent_ledger')
                ->select('represented_parent_ledger.transaction_payment_id')
                ->where('represented_parent_ledger.business_id', $businessId)
                ->whereIn('represented_parent_ledger.contact_id', $customerIds)
                ->whereNotNull('represented_parent_ledger.transaction_payment_id')
                ->where('represented_parent_ledger.transaction_payment_id', '>', 0);

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $representedParents->whereNull('represented_parent_ledger.deleted_at');
            }

            $query->where(function (Builder $parentScope) use ($paymentAlias, $representedParents) {
                $parentScope->whereNull($paymentAlias . '.parent_id')
                    ->orWhereNotIn($paymentAlias . '.parent_id', $representedParents);
            });
        }

        /*
         * IS2307 performance follow-up - Bulk Payment compatibility.
         *
         * The previous aggregate path started from transaction_payments twice
         * (child + anchor) and joined them by payment_ref_no before reaching the
         * relatively small contact_ledgers table.  On tenants with 100k+ payment
         * rows that self-join made /customers/register-total-due noticeably slow.
         *
         * Build the small represented-reference set from contact_ledgers first,
         * then anti-join that DISTINCT set to the candidate payments.  The rule is
         * unchanged: for the same customer, if a payment reference is already
         * represented by contact_ledgers, do not add another fallback payment for
         * that reference.  Only the query shape changes.
         */
        if (SchemaCache::hasColumn('transaction_payments', 'payment_ref_no')) {
            $representedReferences = DB::table('contact_ledgers as component_ref_ledger')
                ->join(
                    'transaction_payments as component_ref_anchor',
                    'component_ref_anchor.id',
                    '=',
                    'component_ref_ledger.transaction_payment_id'
                )
                ->where('component_ref_ledger.business_id', $businessId)
                ->whereIn('component_ref_ledger.contact_id', $customerIds)
                ->whereNotNull('component_ref_anchor.payment_ref_no')
                ->where('component_ref_anchor.payment_ref_no', '!=', '')
                ->selectRaw(
                    'component_ref_ledger.contact_id AS represented_contact_id, ' .
                    'component_ref_anchor.payment_ref_no AS represented_payment_ref_no'
                )
                ->distinct();

            if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                $representedReferences->where('component_ref_anchor.business_id', $businessId);
            }
            if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                $representedReferences->whereNull('component_ref_anchor.deleted_at');
            }
            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $representedReferences->whereNull('component_ref_ledger.deleted_at');
            }

            $representedAlias = $directPaymentFor
                ? 'component_represented_direct_ref'
                : 'component_represented_linked_ref';

            $query->leftJoinSub(
                $representedReferences,
                $representedAlias,
                function ($join) use (
                    $representedAlias,
                    $paymentAlias,
                    $transactionAlias,
                    $directPaymentFor
                ) {
                    $join->on(
                        $representedAlias . '.represented_payment_ref_no',
                        '=',
                        $paymentAlias . '.payment_ref_no'
                    );

                    if ($directPaymentFor
                        && SchemaCache::hasColumn('transaction_payments', 'payment_for')) {
                        $join->on(
                            $representedAlias . '.represented_contact_id',
                            '=',
                            $paymentAlias . '.payment_for'
                        );
                    } else {
                        $join->on(
                            $representedAlias . '.represented_contact_id',
                            '=',
                            $transactionAlias . '.contact_id'
                        );
                    }
                }
            );

            $query->whereNull($representedAlias . '.represented_payment_ref_no');
        }
    }

    private function applyMissingPaymentLedgerFilter(
        Builder $query,
        string $paymentAlias,
        string $transactionAlias,
        ?string $businessExpression = null,
        ?string $contactExpression = null
    ): void {
        if (!$this->canUseContactLedgers()) {
            return;
        }

        if (SchemaCache::hasColumn('contact_ledgers', 'transaction_payment_id')) {
            $hasParent = SchemaCache::hasColumn('transaction_payments', 'parent_id');
            $hasReference = SchemaCache::hasColumn('transaction_payments', 'payment_ref_no');

            $query->whereNotExists(function (Builder $ledger) use ($paymentAlias, $transactionAlias, $hasParent, $hasReference, $businessExpression, $contactExpression) {
                /*
                 |--------------------------------------------------------------
                 | S641 + IS2264: one logical payment = one customer-ledger row.
                 |--------------------------------------------------------------
                 |
                 | Direct Pay Due rows are represented by the exact
                 | transaction_payment_id. Bulk Payment is different: one receipt
                 | is stored as several invoice-allocation rows sharing the same
                 | payment_ref_no, while contact_ledgers intentionally stores one
                 | aggregate row linked to only the anchor allocation.
                 |
                 | Therefore a non-anchor Bulk allocation must also be considered
                 | represented when its payment reference matches the payment row
                 | linked by contact_ledgers. Without this, every extra invoice
                 | allocation is appended again by missingPaymentRows(), producing
                 | duplicate-looking credits in the Customer Ledger on tenants
                 | that have contact_ledgers.transaction_payment_id.
                 */
                $businessExpression = $businessExpression ?: (SchemaCache::hasColumn('transaction_payments', 'business_id')
                    ? "COALESCE({$transactionAlias}.business_id, {$paymentAlias}.business_id)"
                    : "{$transactionAlias}.business_id");

                $contactExpression = $contactExpression ?: (SchemaCache::hasColumn('transaction_payments', 'payment_for')
                    ? "COALESCE({$transactionAlias}.contact_id, {$paymentAlias}.payment_for)"
                    : "{$transactionAlias}.contact_id");

                $ledger->selectRaw('1')
                    ->from('contact_ledgers as represented_payment_ledger');

                if ($hasReference) {
                    $ledger->leftJoin(
                        'transaction_payments as represented_payment_anchor',
                        'represented_payment_anchor.id',
                        '=',
                        'represented_payment_ledger.transaction_payment_id'
                    );
                }

                $ledger->whereRaw("represented_payment_ledger.business_id = {$businessExpression}")
                    ->whereRaw("represented_payment_ledger.contact_id = {$contactExpression}")
                    ->where(function (Builder $represented) use ($paymentAlias, $hasParent, $hasReference) {
                        $represented->whereColumn(
                            'represented_payment_ledger.transaction_payment_id',
                            $paymentAlias . '.id'
                        );

                        if ($hasParent) {
                            $represented->orWhere(function (Builder $parent) use ($paymentAlias) {
                                $parent->whereNotNull($paymentAlias . '.parent_id')
                                    ->whereColumn(
                                        'represented_payment_ledger.transaction_payment_id',
                                        $paymentAlias . '.parent_id'
                                    );
                            });
                        }

                        if ($hasReference) {
                            $represented->orWhere(function (Builder $sameReference) use ($paymentAlias) {
                                $sameReference
                                    ->whereNotNull($paymentAlias . '.payment_ref_no')
                                    ->where($paymentAlias . '.payment_ref_no', '!=', '')
                                    ->whereColumn(
                                        'represented_payment_anchor.payment_ref_no',
                                        $paymentAlias . '.payment_ref_no'
                                    );
                            });
                        }
                    });

                if ($hasReference && SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                    $ledger->whereColumn(
                        'represented_payment_anchor.business_id',
                        'represented_payment_ledger.business_id'
                    );
                }

                if ($hasReference && SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                    $ledger->whereNull('represented_payment_anchor.deleted_at');
                }

                if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                    $ledger->whereNull('represented_payment_ledger.deleted_at');
                }
            });

            return;
        }

        /*
         * Same NULL comparison as above, in the fallback branch used when
         * contact_ledgers has no transaction_payment_id column. Fixed the same
         * way, so a schema without that column does not reintroduce the
         * duplicates once the other branch is correct.
         */
        $query->whereNotExists(function (Builder $ledger) use ($paymentAlias, $transactionAlias, $businessExpression, $contactExpression) {
                $businessExpression = $businessExpression ?: (SchemaCache::hasColumn('transaction_payments', 'business_id')
                    ? "COALESCE({$transactionAlias}.business_id, {$paymentAlias}.business_id)"
                    : "{$transactionAlias}.business_id");

                $contactExpression = $contactExpression ?: (SchemaCache::hasColumn('transaction_payments', 'payment_for')
                    ? "COALESCE({$transactionAlias}.contact_id, {$paymentAlias}.payment_for)"
                    : "{$transactionAlias}.contact_id");

            $ledger->selectRaw('1')
                ->from('contact_ledgers as any_payment_customer_ledger')
                ->whereRaw("any_payment_customer_ledger.business_id = {$businessExpression}")
                ->whereRaw("any_payment_customer_ledger.contact_id = {$contactExpression}");

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $ledger->whereNull('any_payment_customer_ledger.deleted_at');
            }
        });
    }

    private function ledgerTypeExpression(string $alias): string
    {
        $hasType = SchemaCache::hasColumn('contact_ledgers', 'type');
        $hasAccType = SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type');

        if ($hasType && $hasAccType) {
            return "LOWER(COALESCE(NULLIF({$alias}.type, ''), NULLIF({$alias}.acc_transaction_type, ''), 'debit'))";
        }
        if ($hasType) {
            return "LOWER(COALESCE(NULLIF({$alias}.type, ''), 'debit'))";
        }
        if ($hasAccType) {
            return "LOWER(COALESCE(NULLIF({$alias}.acc_transaction_type, ''), 'debit'))";
        }

        return "'debit'";
    }

    private function ledgerOpeningExpression(string $ledgerAlias, ?string $transactionAlias): string
    {
        $checks = [];
        if (SchemaCache::hasColumn('contact_ledgers', 'description')) {
            $checks[] = "LOWER(COALESCE({$ledgerAlias}.description, '')) LIKE '%opening%'";
        }
        if ($transactionAlias !== null && SchemaCache::hasColumn('transactions', 'type')) {
            $checks[] = "LOWER(COALESCE({$transactionAlias}.type, '')) = 'opening_balance'";
        }

        return empty($checks) ? '0 = 1' : '(' . implode(' OR ', $checks) . ')';
    }

    private function transactionNetExpression(string $alias): string
    {
        $creditTypes = "'" . implode("','", self::CREDIT_TRANSACTION_TYPES) . "'";

        return "CASE " .
            "WHEN LOWER(COALESCE({$alias}.type, '')) IN ({$creditTypes}) " .
            "THEN -ABS(COALESCE({$alias}.final_total, 0)) " .
            "WHEN COALESCE({$alias}.final_total, 0) < 0 " .
            "THEN COALESCE({$alias}.final_total, 0) " .
            "ELSE ABS(COALESCE({$alias}.final_total, 0)) END";
    }

    private function paymentNetExpression(string $alias): string
    {
        if (SchemaCache::hasColumn('transaction_payments', 'is_return')) {
            return "CASE WHEN COALESCE({$alias}.is_return, 0) = 1 " .
                "THEN ABS(COALESCE({$alias}.amount, 0)) " .
                "ELSE -ABS(COALESCE({$alias}.amount, 0)) END";
        }

        return "-ABS(COALESCE({$alias}.amount, 0))";
    }

    private function openingBalance(int $businessId, int $customerId, ?Customer $customer): float
    {
        if (!SchemaCache::hasColumn('contacts', 'opening_balance')) {
            return 0.0;
        }

        if ($customer
            && (int) $customer->id === $customerId
            && (int) $customer->business_id === $businessId) {
            return (float) ($customer->opening_balance ?? 0);
        }

        $query = DB::table('contacts')
            ->where('business_id', $businessId)
            ->where('id', $customerId);

        if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (float) $query->value('opening_balance');
    }

    private function canUseContactLedgers(): bool
    {
        return SchemaCache::hasTable('contact_ledgers')
            && SchemaCache::hasColumn('contact_ledgers', 'business_id')
            && SchemaCache::hasColumn('contact_ledgers', 'contact_id');
    }

    private function canUseTransactions(): bool
    {
        return SchemaCache::hasTable('transactions')
            && SchemaCache::hasColumn('transactions', 'id')
            && SchemaCache::hasColumn('transactions', 'business_id')
            && SchemaCache::hasColumn('transactions', 'contact_id')
            && SchemaCache::hasColumn('transactions', 'type')
            && SchemaCache::hasColumn('transactions', 'final_total');
    }

    private function canUseTransactionPayments(): bool
    {
        return SchemaCache::hasTable('transaction_payments')
            && SchemaCache::hasColumn('transaction_payments', 'id')
            && SchemaCache::hasColumn('transaction_payments', 'transaction_id')
            && SchemaCache::hasColumn('transaction_payments', 'amount');
    }

    /** @param array<int, int|string> $ids @return array<int, int> */
    /**
     * S635: which of these ids are the business's Walk-In Customer.
     *
     * One query for the whole set, keyed by id so the caller can test with
     * isset(). Returns an empty array on any problem: showing a real balance is
     * correct behaviour, whereas wrongly zeroing a named customer would hide
     * money that is owed.
     *
     * @param  array<int, int>  $ids
     * @return array<int, bool>
     */
    private function walkInCustomerIds(int $businessId, array $ids): array
    {
        try {
            if (empty($ids) || ! SchemaCache::hasColumn('contacts', 'is_default')) {
                return [];
            }

            return DB::table('contacts')
                ->where('business_id', $businessId)
                ->whereIn('id', $ids)
                ->where('is_default', 1)
                ->pluck('id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function normaliseIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /** @return array<int, array{debit: float, credit: float, opening_present: bool}> */
    private function emptyComponents(array $ids): array
    {
        $components = [];
        foreach ($ids as $id) {
            $components[(int) $id] = [
                'debit' => 0.0,
                'credit' => 0.0,
                'opening_present' => false,
            ];
        }

        return $components;
    }

    /**
     * @param array<int, array{debit: float, credit: float, opening_present: bool}> $target
     * @param array<int, array{debit: float, credit: float, opening_present: bool}> $source
     */
    private function mergeComponents(array &$target, array $source): void
    {
        foreach ($source as $customerId => $component) {
            $customerId = (int) $customerId;
            if (!isset($target[$customerId])) {
                continue;
            }

            $target[$customerId]['debit'] += (float) ($component['debit'] ?? 0);
            $target[$customerId]['credit'] += (float) ($component['credit'] ?? 0);
            $target[$customerId]['opening_present'] = $target[$customerId]['opening_present']
                || (bool) ($component['opening_present'] ?? false);
        }
    }

    /** @return array<int, array{debit: float, credit: float, opening_present: bool}> */
    private function componentRowsToArray(Collection $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $customerId = (int) ($row->contact_id ?? 0);
            if ($customerId <= 0) {
                continue;
            }

            $result[$customerId] = [
                'debit' => (float) ($row->debit_total ?? 0),
                'credit' => (float) ($row->credit_total ?? 0),
                'opening_present' => (bool) ($row->opening_present ?? false),
            ];
        }

        return $result;
    }
}
