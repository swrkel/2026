<?php

namespace Modules\Customers\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Support\SchemaCache;
use RuntimeException;

/**
 * Customers-owned bulk payment workflow.
 *
 * This service intentionally uses only Customers module classes and Laravel's
 * database layer. It does not call Contact controllers/views, core App models,
 * accounting-module controllers, Petro controllers, or any other module file.
 * Shared tenant tables are accessed directly through DB/SchemaCache.
 */
class CustomerBulkPaymentService
{
    protected CustomerReceivableService $receivableService;

    public function __construct(?CustomerReceivableService $receivableService = null)
    {
        $this->receivableService = $receivableService ?: new CustomerReceivableService();
    }

    private const PAYABLE_TRANSACTION_TYPES = [
        'sell',
        'sale',
        'credit_sale',
        'customer_credit_sale',
        'cheque_return',
        'direct_customer_loan',
        'customer_loan',
        'property_sell',
        'route_operation',
        'expense',
        'fpos_sale',
        'vat_price_adjustment',
        'opening_balance',
        'fleet_opening_balance',
        'settlement',
        'distribution_sell',
        'distribution_invoice',
    ];

    public function formData(int $businessId): array
    {
        // Do not preload every payment account group on the first page request.
        // The selected group is loaded on demand, reducing the initial page from
        // several account/group queries to one payment-group query.
        return [
            'customers' => $this->customerOptions($businessId),
            'paymentGroups' => $this->paymentGroups($businessId),
            'paymentAccountsByGroup' => [],
            'nextReference' => $this->nextReference($businessId),
            'interestEnabled' => $this->interestEnabled($businessId),
            'shiftNumbers' => $this->shiftNumbers($businessId),
            'today' => Carbon::now()->format('Y-m-d'),
        ];
    }

    public function customerOptions(int $businessId): array
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

        if (SchemaCache::hasColumn('contacts', 'active')) {
            $query->where(function ($inner) {
                $inner->where('active', 1)->orWhereNull('active');
            });
        }

        $nameColumn = SchemaCache::hasColumn('contacts', 'name') ? 'name' : 'id';
        $codeColumn = SchemaCache::hasColumn('contacts', 'contact_id') ? 'contact_id' : null;

        return $query
            ->orderBy($nameColumn)
            ->get(array_values(array_unique(array_merge(['id', $nameColumn], $codeColumn ? [$codeColumn] : []))))
            ->mapWithKeys(function ($row) use ($nameColumn, $codeColumn) {
                $name = trim((string) ($row->{$nameColumn} ?? ''));
                $code = $codeColumn ? trim((string) ($row->{$codeColumn} ?? '')) : '';
                $label = trim(($code !== '' ? $code . ' - ' : '') . ($name !== '' ? $name : ('Customer ' . $row->id)));

                return [(int) $row->id => $label];
            })
            ->toArray();
    }

    /**
     * @return array<string, array{id:string,name:string,method:string}>
     */
    public function paymentGroups(int $businessId): array
    {
        $groups = [];

        if (SchemaCache::hasTable('account_groups')) {
            $query = DB::table('account_groups')
                ->where('business_id', $businessId);

            if (SchemaCache::hasColumn('account_groups', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            // Match the same four account groups used by the existing system
            // Contact / Customer Payment Bulk page. The lower-case fallback
            // keeps tenants with minor name variations compatible.
            if (SchemaCache::hasColumn('account_groups', 'name')) {
                $query->where(function ($inner) {
                    $inner->whereIn('name', [
                        'Cash Account',
                        "Cheques in Hand (Customer's)",
                        'Card',
                        'Bank Account',
                    ])->orWhere(function ($variation) {
                        foreach (['cash', 'card', 'cheque', 'bank'] as $keyword) {
                            $variation->orWhereRaw('LOWER(name) LIKE ?', ['%' . $keyword . '%']);
                        }
                    });
                });
            }

            foreach ($query->orderBy('name')->get(['id', 'name']) as $group) {
                $id = (string) $group->id;
                $name = trim((string) $group->name);
                $groups[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'method' => $this->methodFromLabel($name),
                ];
            }
        }

        if (empty($groups)) {
            foreach ([
                'method_cash' => ['Cash', 'cash'],
                'method_card' => ['Card', 'card'],
                'method_cheque' => ['Cheque', 'cheque'],
                'method_bank_transfer' => ['Bank Transfer', 'bank_transfer'],
            ] as $id => $definition) {
                $groups[$id] = [
                    'id' => $id,
                    'name' => $definition[0],
                    'method' => $definition[1],
                ];
            }
        }

        return $groups;
    }

    public function accountsForGroup(int $businessId, string $groupId): array
    {
        if (!SchemaCache::hasTable('accounts')) {
            return [];
        }

        $group = $this->resolvePaymentGroup($businessId, $groupId);
        if ($group === null) {
            return [];
        }

        $baseQuery = function () use ($businessId) {
            $query = DB::table('accounts')
                ->where('business_id', $businessId);

            if (SchemaCache::hasColumn('accounts', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            // The legacy page intentionally shows every non-main account linked
            // to the selected payment-method group. Do not hide closed/active
            // variations here because several tenant databases use those flags
            // differently and the existing page did not filter them.
            if (SchemaCache::hasColumn('accounts', 'is_main_account')) {
                $query->where(function ($inner) {
                    $inner->where('is_main_account', 0)->orWhereNull('is_main_account');
                });
            }

            return $query;
        };

        $accounts = [];
        $numericGroupId = ctype_digit((string) $groupId) ? (int) $groupId : null;

        if ($numericGroupId !== null) {
            // Current ERP relationship: accounts.asset_type -> account_groups.id.
            foreach (['asset_type', 'account_group_id', 'group_id'] as $column) {
                if (!SchemaCache::hasColumn('accounts', $column)) {
                    continue;
                }

                $accounts = $baseQuery()
                    ->where($column, $numericGroupId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();

                if (!empty($accounts)) {
                    break;
                }
            }
        }

        // Compatibility fallback for old tenant databases where the group link
        // is blank. Restrict results to names appropriate for the selected
        // method so unrelated accounts are never mixed into the dropdown.
        if (empty($accounts)) {
            $keywords = $this->accountNameKeywords($group['method']);
            $fallback = $baseQuery();
            $fallback->where(function ($inner) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $inner->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($keyword) . '%']);
                }
            });
            $accounts = $fallback->orderBy('name')->pluck('name', 'id')->toArray();
        }

        $normalised = [];
        foreach ($accounts as $id => $name) {
            $normalised[(string) $id] = (string) $name;
        }

        return $normalised;
    }

    /**
     * Lightweight balance response used immediately after customer selection.
     * Invoice rows are fetched independently, so the Total Due field does not
     * wait for hundreds of rows to be queried and rendered into HTML.
     */
    public function customerSummary(int $businessId, int $customerId): array
    {
        $customer = $this->customer($businessId, $customerId);
        $openingBalance = SchemaCache::hasColumn('contacts', 'opening_balance')
            ? (float) ($customer->opening_balance ?? 0)
            : 0.0;

        // IS1851: Bulk Payment must display the exact same Total Due used by
        // Customer Register. Both pages now use CustomerReceivableService,
        // preventing the previous max(invoice total, ledger total) mismatch.
        $balances = $this->receivableService->balancesForCustomers(
            $businessId,
            [$customerId],
            [$customerId => $openingBalance]
        );

        return [
            'customer' => $customer,
            'total_due' => (float) ($balances[$customerId] ?? 0.0),
            'points' => $this->customerPoints($customer),
        ];
    }

    /**
     * Invoice rows are intentionally separate from customerSummary(). This lets
     * the browser display the current balance while the detailed table continues
     * loading in parallel.
     */
    public function customerInvoices(int $businessId, int $customerId): Collection
    {
        $customer = $this->customer($businessId, $customerId);
        $invoices = $this->outstandingInvoices($businessId, $customerId, false);

        return $this->withContactOpeningBalance($invoices, $businessId, $customer);
    }

    /**
     * Backward-compatible combined payload.
     */
    public function customerData(int $businessId, int $customerId): array
    {
        $summary = $this->customerSummary($businessId, $customerId);
        $invoices = $this->withContactOpeningBalance(
            $this->outstandingInvoices($businessId, $customerId, false),
            $businessId,
            $summary['customer']
        );

        return $summary + ['invoices' => $invoices];
    }

    public function outstandingInvoices(int $businessId, int $customerId, bool $validateCustomer = true): Collection
    {
        if ($validateCustomer) {
            $this->customer($businessId, $customerId);
        }

        [$query, $payments] = $this->outstandingBaseQuery($businessId, $customerId);
        if ($query === null) {
            return collect();
        }

        $hasType = SchemaCache::hasColumn('transactions', 'type');
        $transactionDate = SchemaCache::hasColumn('transactions', 'transaction_date')
            ? 'transactions.transaction_date'
            : (SchemaCache::hasColumn('transactions', 'created_at')
                ? DB::raw('transactions.created_at as transaction_date')
                : DB::raw('NULL as transaction_date'));

        $columns = [
            'transactions.id',
            $transactionDate,
            SchemaCache::hasColumn('transactions', 'invoice_no') ? 'transactions.invoice_no' : DB::raw("'' as invoice_no"),
            SchemaCache::hasColumn('transactions', 'ref_no') ? 'transactions.ref_no' : DB::raw("'' as ref_no"),
            SchemaCache::hasColumn('transactions', 'order_no') ? 'transactions.order_no' : DB::raw("'' as order_no"),
            $hasType ? 'transactions.type' : DB::raw("'sell' as type"),
            SchemaCache::hasColumn('transactions', 'payment_status') ? 'transactions.payment_status' : DB::raw("'' as payment_status"),
            SchemaCache::hasColumn('transactions', 'final_total') ? 'transactions.final_total' : DB::raw('0 as final_total'),
            SchemaCache::hasColumn('transactions', 'cheque_return_charges') ? 'transactions.cheque_return_charges' : DB::raw('0 as cheque_return_charges'),
            $payments !== null ? DB::raw('COALESCE(bulk_payment_totals.total_paid, 0) as total_paid') : DB::raw('0 as total_paid'),
        ];

        $query->select($columns);
        if (SchemaCache::hasColumn('transactions', 'transaction_date')) {
            $query->orderBy('transactions.transaction_date');
        } elseif (SchemaCache::hasColumn('transactions', 'created_at')) {
            $query->orderBy('transactions.created_at');
        }

        return $query
            ->orderBy('transactions.id')
            ->get()
            ->map(function ($row) {
                $type = strtolower(trim((string) ($row->type ?? 'sell')));
                $finalTotal = (float) ($row->final_total ?? 0);

                if (in_array($type, ['opening_balance', 'fleet_opening_balance', 'advance_payment'], true) && $finalTotal < 0) {
                    return null;
                }

                $gross = max($finalTotal, 0.0);
                if ($type === 'cheque_return') {
                    $gross = abs($finalTotal) + abs((float) ($row->cheque_return_charges ?? 0));
                }

                if ($gross < 0.005) {
                    return null;
                }

                $paid = abs((float) ($row->total_paid ?? 0));
                $outstanding = max($gross - $paid, 0.0);
                if ($outstanding < 0.005) {
                    return null;
                }

                $row->invoice_total = $gross;
                $row->total_paid = $paid;
                $row->outstanding = $outstanding;
                $row->is_opening_balance = in_array($type, ['opening_balance', 'fleet_opening_balance'], true);

                return $row;
            })
            ->filter()
            ->values();
    }

    /**
     * Aggregate the outstanding amount in SQL instead of loading and rendering
     * every invoice before the Total Due field can be updated.
     */
    private function outstandingTotal(int $businessId, int $customerId): float
    {
        [$query, $payments] = $this->outstandingBaseQuery($businessId, $customerId);
        if ($query === null) {
            return 0.0;
        }

        $finalTotal = SchemaCache::hasColumn('transactions', 'final_total')
            ? 'COALESCE(transactions.final_total, 0)'
            : '0';
        $charges = SchemaCache::hasColumn('transactions', 'cheque_return_charges')
            ? 'COALESCE(transactions.cheque_return_charges, 0)'
            : '0';
        $gross = SchemaCache::hasColumn('transactions', 'type')
            ? "CASE WHEN LOWER(COALESCE(transactions.type, '')) = 'cheque_return' " .
              "THEN ABS({$finalTotal}) + ABS({$charges}) ELSE GREATEST({$finalTotal}, 0) END"
            : "GREATEST({$finalTotal}, 0)";
        $paid = $payments !== null
            ? 'ABS(COALESCE(bulk_payment_totals.total_paid, 0))'
            : '0';

        $row = $query
            ->selectRaw("COALESCE(SUM(GREATEST(({$gross}) - ({$paid}), 0)), 0) as total_due")
            ->first();

        return (float) ($row->total_due ?? 0);
    }

    /**
     * Build the payable-transaction query once for both the fast aggregate and
     * the detailed invoice list.
     *
     * @return array{0: mixed, 1: mixed}
     */
    private function outstandingBaseQuery(int $businessId, int $customerId): array
    {
        if (!SchemaCache::hasTable('transactions')) {
            return [null, null];
        }

        $payments = $this->paymentTotalsSubquery($businessId, $customerId);
        $query = DB::table('transactions');
        if ($payments !== null) {
            $query->leftJoinSub($payments, 'bulk_payment_totals', function ($join) {
                $join->on('transactions.id', '=', 'bulk_payment_totals.transaction_id');
            });
        }

        $query->where('transactions.business_id', $businessId)
            ->where('transactions.contact_id', $customerId);

        if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('transactions.deleted_at');
        }

        $hasType = SchemaCache::hasColumn('transactions', 'type');
        $hasCreditFlag = SchemaCache::hasColumn('transactions', 'is_credit_sale');
        if ($hasType || $hasCreditFlag) {
            $query->where(function ($inner) use ($hasType, $hasCreditFlag) {
                if ($hasType) {
                    $inner->whereIn('transactions.type', self::PAYABLE_TRANSACTION_TYPES);
                }
                if ($hasCreditFlag) {
                    $hasType
                        ? $inner->orWhere('transactions.is_credit_sale', 1)
                        : $inner->where('transactions.is_credit_sale', 1);
                }
            });
        }

        if (SchemaCache::hasColumn('transactions', 'payment_status')) {
            $query->where(function ($inner) {
                $inner->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->orWhereNull('transactions.payment_status')
                    ->orWhere('transactions.payment_status', '');
            });
        }

        return [$query, $payments];
    }

    /**
     * Aggregate payments only for the selected customer's transaction IDs.
     * The earlier query grouped every payment in the business before joining,
     * which became slow on large tenant databases.
     */
    private function paymentTotalsSubquery(int $businessId, int $customerId)
    {
        if (!SchemaCache::hasTable('transaction_payments')
            || !SchemaCache::hasColumn('transaction_payments', 'transaction_id')) {
            return null;
        }

        $query = DB::table('transaction_payments as tp')
            ->join('transactions as payment_tx', 'tp.transaction_id', '=', 'payment_tx.id')
            ->where('payment_tx.business_id', $businessId)
            ->where('payment_tx.contact_id', $customerId)
            ->whereNotNull('tp.transaction_id');

        if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
            $query->where('tp.business_id', $businessId);
        }
        if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('tp.deleted_at');
        }
        if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('payment_tx.deleted_at');
        }

        $amount = SchemaCache::hasColumn('transaction_payments', 'amount')
            ? 'COALESCE(tp.amount, 0)'
            : '0';
        if (SchemaCache::hasColumn('transaction_payments', 'is_return')) {
            $amount = "CASE WHEN COALESCE(tp.is_return, 0) = 1 THEN 0 ELSE {$amount} END";
        }

        return $query
            ->select('tp.transaction_id')
            ->selectRaw("SUM({$amount}) as total_paid")
            ->groupBy('tp.transaction_id');
    }

    /**
     * One-row ledger aggregate used by the Bulk Payment summary endpoint. It
     * avoids the broader ledger reconciliation workflow because the invoice
     * aggregate already covers sales and linked payments for this page.
     */
    private function fastLedgerBalance(int $businessId, int $customerId, Customer $customer): float
    {
        $openingBalance = SchemaCache::hasColumn('contacts', 'opening_balance')
            ? (float) ($customer->opening_balance ?? 0)
            : 0.0;

        if (!SchemaCache::hasTable('contact_ledgers')) {
            return $openingBalance;
        }

        $table = 'contact_ledgers';
        $query = DB::table($table . ' as cl')
            ->where('cl.business_id', $businessId)
            ->where('cl.contact_id', $customerId);

        if (SchemaCache::hasColumn($table, 'deleted_at')) {
            $query->whereNull('cl.deleted_at');
        }

        $hasTransactionJoin = SchemaCache::hasColumn($table, 'transaction_id')
            && SchemaCache::hasTable('transactions');
        if ($hasTransactionJoin) {
            $query->leftJoin('transactions as ledger_tx', 'cl.transaction_id', '=', 'ledger_tx.id');
        }

        $type = SchemaCache::hasColumn($table, 'type')
            ? "LOWER(COALESCE(cl.type, ''))"
            : (SchemaCache::hasColumn($table, 'acc_transaction_type')
                ? "LOWER(COALESCE(cl.acc_transaction_type, ''))"
                : "'debit'");
        $amount = SchemaCache::hasColumn($table, 'amount')
            ? 'ABS(COALESCE(cl.amount, 0))'
            : '0';

        $openingChecks = [];
        if (SchemaCache::hasColumn($table, 'description')) {
            $openingChecks[] = "LOWER(COALESCE(cl.description, '')) LIKE '%opening%'";
        }
        if ($hasTransactionJoin && SchemaCache::hasColumn('transactions', 'type')) {
            $openingChecks[] = "LOWER(COALESCE(ledger_tx.type, '')) IN ('opening_balance', 'fleet_opening_balance')";
        }
        $openingExpression = empty($openingChecks) ? '0 = 1' : '(' . implode(' OR ', $openingChecks) . ')';

        $row = $query->selectRaw(
            "COALESCE(SUM(CASE WHEN {$type} = 'credit' THEN 0 ELSE {$amount} END), 0) as debit_total, " .
            "COALESCE(SUM(CASE WHEN {$type} = 'credit' THEN {$amount} ELSE 0 END), 0) as credit_total, " .
            "MAX(CASE WHEN {$openingExpression} THEN 1 ELSE 0 END) as opening_present"
        )->first();

        $balance = (float) ($row->debit_total ?? 0) - (float) ($row->credit_total ?? 0);
        if (!(bool) ($row->opening_present ?? false)) {
            $balance += $openingBalance;
        }

        return $balance;
    }

    private function customerPoints(Customer $customer): float
    {
        foreach (['total_rp', 'loyalty_points', 'reward_points'] as $column) {
            if (SchemaCache::hasColumn('contacts', $column)) {
                return (float) ($customer->{$column} ?? 0);
            }
        }

        return 0.0;
    }

    /**
     * @throws ValidationException
     */
    public function save(int $businessId, int $userId, array $input): array
    {
        $customerId = (int) ($input['customer_id'] ?? 0);
        $customer = $this->customer($businessId, $customerId);

        $groupId = trim((string) ($input['payment_group_id'] ?? ''));
        $group = $this->resolvePaymentGroup($businessId, $groupId);
        if ($group === null) {
            throw ValidationException::withMessages(['payment_group_id' => 'Please select a valid payment method.']);
        }

        $accountId = (int) ($input['account_id'] ?? 0);
        $accountOptions = $this->accountsForGroup($businessId, $groupId);
        if ($accountId <= 0 || !array_key_exists($accountId, $accountOptions)) {
            throw ValidationException::withMessages(['account_id' => 'Please select a payment account linked with the selected method.']);
        }

        $paymentAmount = $this->amount($input['payment_amount'] ?? 0);
        if ($paymentAmount <= 0) {
            throw ValidationException::withMessages(['payment_amount' => 'Please enter a valid payment amount.']);
        }

        $paymentDate = $this->dateTime($input['transaction_date'] ?? null);
        $chequeDate = $this->dateOnly($input['cheque_date'] ?? null);
        $chequeNumber = trim((string) ($input['cheque_number'] ?? ''));
        $bankName = trim((string) ($input['bank_name'] ?? ''));
        $cardNumber = trim((string) ($input['card_number'] ?? ''));
        $cardType = trim((string) ($input['card_type'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));

        if ($group['method'] === 'cheque') {
            if ($chequeNumber === '' || $bankName === '' || $chequeDate === null) {
                throw ValidationException::withMessages([
                    'cheque_number' => 'Cheque Number, Bank Name and Cheque Date are required for cheque payments.',
                ]);
            }
            if ($this->duplicateChequeExists($businessId, $chequeNumber, $bankName)) {
                throw ValidationException::withMessages([
                    'cheque_number' => 'A cheque with the same number and bank name already exists.',
                ]);
            }
        }

        $availableInvoices = $this->customerInvoices($businessId, $customerId)->keyBy('id');
        $allocationInput = is_array($input['allocations'] ?? null) ? $input['allocations'] : [];
        $interestInput = is_array($input['interest'] ?? null) ? $input['interest'] : [];
        $allocations = [];
        $totalPrincipal = 0.0;
        $totalInterest = 0.0;

        foreach ($allocationInput as $transactionId => $rawAmount) {
            $transactionId = (int) $transactionId;
            $principal = $this->amount($rawAmount);
            $interest = $this->amount($interestInput[$transactionId] ?? 0);

            if ($principal <= 0 && $interest <= 0) {
                continue;
            }

            $invoice = $availableInvoices->get($transactionId);
            if (!$invoice) {
                throw ValidationException::withMessages([
                    'allocations' => 'One of the selected invoices is no longer outstanding. Please reload the customer.',
                ]);
            }

            if ($principal - (float) $invoice->outstanding > 0.005) {
                throw ValidationException::withMessages([
                    'allocations' => 'The payment allocation for ' . $this->invoiceLabel($invoice) . ' exceeds its outstanding amount.',
                ]);
            }

            $resolvedTransactionId = $transactionId;
            if (!empty($invoice->is_contact_opening_balance)) {
                $resolvedTransactionId = $this->ensureContactOpeningBalanceTransaction(
                    $businessId,
                    $userId,
                    $customer,
                    $invoice
                );
            }

            $allocations[] = [
                'transaction_id' => $resolvedTransactionId,
                'principal' => round($principal, 4),
                'interest' => round($interest, 4),
                'invoice' => $invoice,
            ];
            $totalPrincipal += $principal;
            $totalInterest += $interest;
        }

        $totalApplied = $totalPrincipal + $totalInterest;
        if ($totalApplied - $paymentAmount > 0.005) {
            throw ValidationException::withMessages([
                'payment_amount' => 'Allocated invoice payments and interest exceed the payment amount.',
            ]);
        }

        $unallocated = max($paymentAmount - $totalApplied, 0);
        if (empty($allocations) && $unallocated <= 0.005) {
            throw ValidationException::withMessages([
                'allocations' => 'Please select at least one outstanding invoice or enter an amount that can be kept as a customer advance.',
            ]);
        }

        if (!SchemaCache::hasTable('transaction_payments')) {
            throw new RuntimeException('The transaction_payments table is required for Bulk Payment.');
        }

        $customerCredit = $totalPrincipal + $unallocated;
        $receivableAccountId = $this->accountIdByName($businessId, ['Accounts Receivable']);
        $interestAccountId = $totalInterest > 0.005
            ? $this->accountIdByName($businessId, [
                'Customer Interest Account',
                'Customer Interest Income',
                'Interest Income',
            ])
            : null;

        if (SchemaCache::hasTable('account_transactions') && $receivableAccountId === null && $customerCredit > 0.005) {
            throw ValidationException::withMessages([
                'account_id' => 'Accounts Receivable is not configured for this business.',
            ]);
        }
        if (SchemaCache::hasTable('account_transactions') && $totalInterest > 0.005 && $interestAccountId === null) {
            throw ValidationException::withMessages([
                'interest' => 'A Customer Interest Income account is required before interest can be collected.',
            ]);
        }

        $createdPaymentIds = [];
        $finalReference = '';

        // IS2264: prevent a double-click/network retry from creating a second
        // Bulk Payment. Old already-open forms without the token remain valid.
        $submissionToken = $this->normaliseSubmissionToken($input['submission_token'] ?? '');
        if (!$this->reserveSubmissionToken($businessId, $customerId, $submissionToken)) {
            throw ValidationException::withMessages([
                'payment_amount' => 'This Bulk Payment is already being processed or has already been saved. Please reload before trying again.',
            ]);
        }

        try {
            DB::transaction(function () use (
                $businessId,
                $userId,
                $customer,
                $group,
                $accountId,
                $paymentAmount,
                $paymentDate,
                $chequeDate,
                $chequeNumber,
                $bankName,
                $cardNumber,
                $cardType,
                $note,
                $input,
                $allocations,
                $totalInterest,
                $unallocated,
                $customerCredit,
                $receivableAccountId,
                $interestAccountId,
                &$createdPaymentIds,
                &$finalReference
        ) {
            $finalReference = $this->nextReference($businessId, true, $paymentDate);

            $duplicateReference = DB::table('transaction_payments')
                ->where('business_id', $businessId)
                ->where('payment_ref_no', $finalReference);
            if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                $duplicateReference->whereNull('deleted_at');
            }
            if ($duplicateReference->exists()) {
                throw ValidationException::withMessages([
                    'payment_reference' => 'This Bulk Payment reference has already been processed. Reload the page before saving again.',
                ]);
            }

            foreach ($allocations as $allocation) {
                $paymentId = $this->insertTransactionPayment([
                    'business_id' => $businessId,
                    'transaction_id' => $allocation['transaction_id'],
                    'amount' => $allocation['principal'],
                    'interest' => $allocation['interest'],
                    'method' => $group['method'],
                    'payment_ref_no' => $finalReference,
                    'payment_for' => (int) $customer->id,
                    'paid_on' => $paymentDate,
                    'created_by' => $userId,
                    'paid_in_type' => 'customer_bulk',
                    'account_id' => $accountId,
                    'card_number' => $cardNumber !== '' ? $cardNumber : null,
                    'card_type' => $cardType !== '' ? $cardType : null,
                    'bank_name' => $bankName !== '' ? $bankName : null,
                    'cheque_date' => $chequeDate,
                    'cheque_number' => $chequeNumber !== '' ? $chequeNumber : null,
                    'shift_number' => $input['shift_number'] ?? null,
                    'post_dated_cheque' => !empty($input['post_dated_cheque']) ? 1 : 0,
                    'update_post_dated_cheque' => !empty($input['update_post_dated_cheque']) ? 1 : 0,
                    'note' => $note !== '' ? $note : 'Bulk Payment ' . $finalReference,
                    'is_advance' => 0,
                ]);

                $createdPaymentIds[] = $paymentId;
                $this->updateTransactionPaymentStatus($businessId, $allocation['transaction_id']);
            }

            if ($unallocated > 0.005) {
                $createdPaymentIds[] = $this->insertTransactionPayment([
                    'business_id' => $businessId,
                    'transaction_id' => null,
                    'amount' => round($unallocated, 4),
                    'interest' => 0,
                    'method' => $group['method'],
                    'payment_ref_no' => $finalReference,
                    'payment_for' => (int) $customer->id,
                    'paid_on' => $paymentDate,
                    'created_by' => $userId,
                    'paid_in_type' => 'customer_bulk',
                    'account_id' => $accountId,
                    'card_number' => $cardNumber !== '' ? $cardNumber : null,
                    'card_type' => $cardType !== '' ? $cardType : null,
                    'bank_name' => $bankName !== '' ? $bankName : null,
                    'cheque_date' => $chequeDate,
                    'cheque_number' => $chequeNumber !== '' ? $chequeNumber : null,
                    'shift_number' => $input['shift_number'] ?? null,
                    'post_dated_cheque' => !empty($input['post_dated_cheque']) ? 1 : 0,
                    'update_post_dated_cheque' => !empty($input['update_post_dated_cheque']) ? 1 : 0,
                    'note' => 'Unallocated customer advance from ' . $finalReference,
                    'is_advance' => 1,
                ]);
            }

            $anchorPaymentId = !empty($createdPaymentIds) ? (int) reset($createdPaymentIds) : null;
            $subType = $this->ledgerSubType($group['method']);

            // One aggregate customer-ledger row keeps statements clean while
            // invoice-level payment rows remain available for allocation/audit.
            if ($customerCredit > 0.005) {
                $this->insertContactLedger([
                    'business_id' => $businessId,
                    'contact_id' => (int) $customer->id,
                    'amount' => round($customerCredit, 4),
                    'type' => 'credit',
                    'acc_transaction_type' => 'credit',
                    'sub_type' => $subType,
                    'operation_date' => $paymentDate,
                    'description' => 'Bulk Payment ' . $finalReference,
                    'note' => 'Bulk Payment ' . $finalReference,
                    'transaction_payment_id' => $anchorPaymentId,
                    'created_by' => $userId,
                    'payment_method' => $group['method'],
                    'interest' => round($totalInterest, 4),
                ]);
            }

            // Accounting entry: payment account debit, receivable credit and
            // optional interest-income credit. All code remains in Customers.
            $this->insertAccountTransaction([
                'business_id' => $businessId,
                'account_id' => $accountId,
                'amount' => round($paymentAmount, 4),
                'interest' => round($totalInterest, 4),
                'type' => 'debit',
                'sub_type' => $subType,
                'operation_date' => $paymentDate,
                'created_by' => $userId,
                'transaction_payment_id' => $anchorPaymentId,
                'payment_method' => $group['method'],
                'note' => 'Bulk Payment ' . $finalReference,
            ]);

            if ($receivableAccountId !== null && $customerCredit > 0.005) {
                $this->insertAccountTransaction([
                    'business_id' => $businessId,
                    'account_id' => $receivableAccountId,
                    'amount' => round($customerCredit, 4),
                    'interest' => 0,
                    'type' => 'credit',
                    'sub_type' => $subType,
                    'operation_date' => $paymentDate,
                    'created_by' => $userId,
                    'transaction_payment_id' => $anchorPaymentId,
                    'payment_method' => $group['method'],
                    'note' => 'Accounts Receivable - ' . $finalReference,
                ]);
            }

            if ($totalInterest > 0.005 && $interestAccountId !== null) {
                    $this->insertAccountTransaction([
                        'business_id' => $businessId,
                        'account_id' => $interestAccountId,
                        'amount' => round($totalInterest, 4),
                        'interest' => 0,
                        'type' => 'credit',
                        'sub_type' => 'customer_interest',
                        'operation_date' => $paymentDate,
                        'created_by' => $userId,
                        'transaction_payment_id' => $anchorPaymentId,
                        'payment_method' => $group['method'],
                        'note' => 'Customer Interest - ' . $finalReference,
                    ]);
            }
            });
        } catch (\Throwable $e) {
            // Validation/database failures must allow the same open form to retry.
            $this->releaseSubmissionToken($businessId, $customerId, $submissionToken);
            throw $e;
        }

        /*
         * S631: Bulk Payment on the Customer Register never sent the Payment
         * Received notification either - like the Pay Due / Advance Payment
         * actions, this standalone service simply had no call to the
         * notification layer.
         *
         * Sent after DB::transaction() has committed, and delegated to the same
         * helper the other Customer Register actions use so the message and its
         * tags are identical. It swallows its own failures: the payment is
         * already saved and must not be undone by a dead SMS gateway.
         */
        try {
            /*
             * S637-1: the SMS must quote the same two numbers the screen does.
             *
             *   Paid Amount    = $paymentAmount, the figure the user entered.
             *                    Not the allocated total and not the principal
             *                    net of interest - what they typed is what they
             *                    are told they paid.
             *
             *   Ledger Balance = fastLedgerBalance() re-read HERE, after the
             *                    transaction has committed, so it is the balance
             *                    REMAINING. This is the module's own
             *                    contact_ledgers calculation - the one this
             *                    screen already displays - rather than
             *                    ContactUtil::getCustomerBalance(), which reads
             *                    transactions/transaction_payments and will not
             *                    agree with it.
             */
            $ledgerBalanceAfterPayment = $this->fastLedgerBalance(
                $businessId,
                (int) $customer->id,
                $customer->fresh() ?: $customer
            );

            app(\Modules\Customers\Services\CustomerPaymentActionService::class)
                ->sendCustomerPaymentReceivedNotification(
                    $businessId,
                    $customer,
                    (float) $paymentAmount,
                    $paymentDate ?? now()->format('Y-m-d H:i:s'),
                    $finalReference,
                    'Bulk Payment',
                    (float) $ledgerBalanceAfterPayment
                );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('S631: Payment Received notification failed after a saved bulk payment.', [
                'business_id' => $businessId,
                'contact_id' => $customer->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'success' => true,
            'reference' => $finalReference,
            'customer_id' => (int) $customer->id,
            'payment_amount' => round($paymentAmount, 4),
            'allocated_amount' => round($totalApplied, 4),
            'unallocated_amount' => round($unallocated, 4),
            'payment_ids' => $createdPaymentIds,
        ];
    }

    public function receiptData(int $businessId, string $reference): array
    {
        $reference = $this->normaliseReference($reference);
        if ($reference === '' || !SchemaCache::hasTable('transaction_payments')) {
            throw new RuntimeException('Bulk Payment receipt not found.');
        }

        $payments = DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->where('payment_ref_no', $reference);

        if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            $payments->whereNull('deleted_at');
        }

        $rows = $payments->orderBy('id')->get();
        if ($rows->isEmpty()) {
            throw new RuntimeException('Bulk Payment receipt not found.');
        }

        $customerId = (int) ($rows->first()->payment_for ?? 0);
        $customer = $this->customer($businessId, $customerId);
        $transactionIds = $rows->pluck('transaction_id')->filter()->map(fn ($id) => (int) $id)->all();
        $transactions = empty($transactionIds) || !SchemaCache::hasTable('transactions')
            ? collect()
            : DB::table('transactions')->where('business_id', $businessId)->whereIn('id', $transactionIds)->get()->keyBy('id');

        $allocations = $rows->map(function ($payment) use ($transactions) {
            $transaction = !empty($payment->transaction_id) ? $transactions->get((int) $payment->transaction_id) : null;

            return (object) [
                'transaction_id' => $payment->transaction_id ?? null,
                'invoice_no' => $transaction->invoice_no ?? ($transaction->ref_no ?? 'Customer Advance'),
                'transaction_date' => $transaction->transaction_date ?? null,
                'amount' => (float) ($payment->amount ?? 0),
                'interest' => (float) ($payment->interest ?? 0),
                'is_advance' => (int) ($payment->is_advance ?? 0),
            ];
        });

        return [
            'reference' => $reference,
            'customer' => $customer,
            'payment' => $rows->first(),
            'allocations' => $allocations,
            'total' => (float) $rows->sum(fn ($row) => (float) ($row->amount ?? 0) + (float) ($row->interest ?? 0)),
        ];
    }

    private function customer(int $businessId, int $customerId): Customer
    {
        if ($customerId <= 0 || !SchemaCache::hasTable('contacts')) {
            throw ValidationException::withMessages(['customer_id' => 'Please select a customer.']);
        }

        $query = Customer::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both']);

        if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (SchemaCache::hasColumn('contacts', 'active')) {
            $query->where('active', 1);
        }

        $customer = $query->find($customerId);
        if (!$customer) {
            throw ValidationException::withMessages(['customer_id' => 'The selected customer is not available for this business.']);
        }

        return $customer;
    }

    private function resolvePaymentGroup(int $businessId, string $groupId): ?array
    {
        $groups = $this->paymentGroups($businessId);
        return $groups[$groupId] ?? null;
    }

    private function methodFromLabel(string $label): string
    {
        $label = strtolower($label);
        if (str_contains($label, 'card')) {
            return 'card';
        }
        if (str_contains($label, 'cheque')) {
            return 'cheque';
        }
        if (str_contains($label, 'bank')) {
            return 'bank_transfer';
        }

        return 'cash';
    }

    private function accountNameKeywords(string $method): array
    {
        return match ($method) {
            'card' => ['card'],
            'cheque' => ['cheque'],
            'bank_transfer' => ['bank'],
            default => ['cash'],
        };
    }

    private function interestEnabled(int $businessId): bool
    {
        if (!SchemaCache::hasTable('business') && !SchemaCache::hasTable('businesses')) {
            return false;
        }

        $table = SchemaCache::hasTable('business') ? 'business' : 'businesses';
        if (!SchemaCache::hasColumn($table, 'customer_interest_deduct_option')) {
            return false;
        }

        return (int) DB::table($table)->where('id', $businessId)->value('customer_interest_deduct_option') === 1;
    }

    private function shiftNumbers(int $businessId): array
    {
        if (!SchemaCache::hasTable('petro_daily_shifts') || !SchemaCache::hasColumn('petro_daily_shifts', 'shift_no')) {
            return [];
        }

        $query = DB::table('petro_daily_shifts')->where('business_id', $businessId)->whereNotNull('shift_no');
        if (SchemaCache::hasColumn('petro_daily_shifts', 'status')) {
            $query->where('status', 0);
        }

        return $query->orderByDesc('id')->pluck('shift_no')->unique()->values()->mapWithKeys(fn ($shift) => [(string) $shift => (string) $shift])->toArray();
    }

    private function nextReference(int $businessId, bool $lock = false, mixed $paidOn = null): string
    {
        $service = app(CustomerPaymentReferenceService::class);

        return $lock
            ? $service->next('customer_payments', $businessId, $paidOn)
            : $service->preview('customer_payments', $businessId, $paidOn);
    }

    private function duplicateChequeExists(int $businessId, string $chequeNumber, string $bankName): bool
    {
        if (!SchemaCache::hasTable('transaction_payments')
            || !SchemaCache::hasColumn('transaction_payments', 'cheque_number')
            || !SchemaCache::hasColumn('transaction_payments', 'bank_name')) {
            return false;
        }

        $query = DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(COALESCE(cheque_number, \'\')) = ?', [strtolower($chequeNumber)])
            ->whereRaw('LOWER(COALESCE(bank_name, \'\')) = ?', [strtolower($bankName)]);

        if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    private function insertTransactionPayment(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? now();
        $data['updated_at'] = $data['updated_at'] ?? now();
        $insert = $this->availableColumns('transaction_payments', $data);

        return (int) DB::table('transaction_payments')->insertGetId($insert);
    }

    private function insertContactLedger(array $data): void
    {
        if (!SchemaCache::hasTable('contact_ledgers')) {
            return;
        }

        $data['created_at'] = $data['created_at'] ?? now();
        $data['updated_at'] = $data['updated_at'] ?? now();
        $insert = $this->availableColumns('contact_ledgers', $data);

        // IS2276: Bulk Payment also owns one aggregate customer-ledger row per
        // anchor transaction payment.  Keep that write idempotent so a retry of
        // the ledger-posting portion cannot create a second visible/balance row.
        $paymentId = (int) ($insert['transaction_payment_id'] ?? 0);
        $businessId = (int) ($insert['business_id'] ?? 0);
        $contactId = (int) ($insert['contact_id'] ?? 0);
        if ($paymentId > 0 && $businessId > 0 && $contactId > 0
            && SchemaCache::hasColumn('contact_ledgers', 'transaction_payment_id')) {
            $existing = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $contactId)
                ->where('transaction_payment_id', $paymentId);

            if (SchemaCache::hasColumn('contact_ledgers', 'amount') && array_key_exists('amount', $insert)) {
                $existing->whereRaw('ABS(COALESCE(amount, 0) - ?) < 0.0001', [abs((float) $insert['amount'])]);
            }

            $type = strtolower(trim((string) ($insert['type'] ?? $insert['acc_transaction_type'] ?? '')));
            if ($type !== '') {
                $hasLedgerType = SchemaCache::hasColumn('contact_ledgers', 'type');
                $hasLedgerAccType = SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type');
                if ($hasLedgerType && $hasLedgerAccType) {
                    $existing->whereRaw(
                        "LOWER(COALESCE(NULLIF(type, ''), NULLIF(acc_transaction_type, ''), 'debit')) = ?",
                        [$type]
                    );
                } elseif ($hasLedgerType) {
                    $existing->whereRaw("LOWER(COALESCE(NULLIF(type, ''), 'debit')) = ?", [$type]);
                } elseif ($hasLedgerAccType) {
                    $existing->whereRaw("LOWER(COALESCE(NULLIF(acc_transaction_type, ''), 'debit')) = ?", [$type]);
                }
            }

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $existing->whereNull('deleted_at');
            }

            if ($existing->exists()) {
                return;
            }
        }

        if (!empty($insert)) {
            DB::table('contact_ledgers')->insert($insert);
        }
    }

    private function insertAccountTransaction(array $data): void
    {
        if (!SchemaCache::hasTable('account_transactions')) {
            return;
        }

        $data['created_at'] = $data['created_at'] ?? now();
        $data['updated_at'] = $data['updated_at'] ?? now();
        $insert = $this->availableColumns('account_transactions', $data);
        if (!empty($insert)) {
            DB::table('account_transactions')->insert($insert);
        }
    }

    private function updateTransactionPaymentStatus(int $businessId, int $transactionId): void
    {
        if (!SchemaCache::hasTable('transactions') || !SchemaCache::hasColumn('transactions', 'payment_status')) {
            return;
        }

        $transaction = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('id', $transactionId)
            ->first();
        if (!$transaction) {
            return;
        }

        $payments = DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->where('transaction_id', $transactionId);
        if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
            $payments->whereNull('deleted_at');
        }
        if (SchemaCache::hasColumn('transaction_payments', 'is_return')) {
            $payments->where(function ($inner) {
                $inner->where('is_return', 0)->orWhereNull('is_return');
            });
        }

        $paid = (float) $payments->sum('amount');
        $gross = abs((float) ($transaction->final_total ?? 0));
        if (strtolower((string) ($transaction->type ?? '')) === 'cheque_return') {
            $gross += abs((float) ($transaction->cheque_return_charges ?? 0));
        }

        $status = $paid <= 0.005 ? 'due' : (($gross - $paid) <= 0.005 ? 'paid' : 'partial');
        $update = ['payment_status' => $status];
        if (SchemaCache::hasColumn('transactions', 'updated_at')) {
            $update['updated_at'] = now();
        }

        DB::table('transactions')->where('business_id', $businessId)->where('id', $transactionId)->update($update);
    }

    private function accountIdByName(int $businessId, array $names): ?int
    {
        if (!SchemaCache::hasTable('accounts') || !SchemaCache::hasColumn('accounts', 'name')) {
            return null;
        }

        $lowerNames = array_map('strtolower', $names);
        $query = DB::table('accounts')->where('business_id', $businessId)
            ->where(function ($inner) use ($lowerNames) {
                foreach ($lowerNames as $name) {
                    $inner->orWhereRaw('LOWER(name) = ?', [$name]);
                }
            });

        if (SchemaCache::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $id = $query->orderBy('id')->value('id');
        return $id !== null ? (int) $id : null;
    }

    private function normaliseSubmissionToken($token): string
    {
        $token = substr(trim((string) $token), 0, 100);
        return preg_replace('/[^A-Za-z0-9\-]/', '', $token) ?: '';
    }

    private function reserveSubmissionToken(int $businessId, int $customerId, string $token): bool
    {
        if ($token === '') {
            return true;
        }

        try {
            return \Illuminate\Support\Facades\Cache::add(
                $this->submissionTokenCacheKey($businessId, $customerId, $token),
                1,
                now()->addMinutes(20)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Customers Bulk Payment idempotency cache unavailable', [
                'business_id' => $businessId,
                'customer_id' => $customerId,
                'message' => $e->getMessage(),
            ]);
            return true;
        }
    }

    private function releaseSubmissionToken(int $businessId, int $customerId, string $token): void
    {
        if ($token === '') {
            return;
        }

        try {
            \Illuminate\Support\Facades\Cache::forget(
                $this->submissionTokenCacheKey($businessId, $customerId, $token)
            );
        } catch (\Throwable $e) {
            // Database failure is already being re-thrown; cache cleanup is best effort.
        }
    }

    private function submissionTokenCacheKey(int $businessId, int $customerId, string $token): string
    {
        return 'customers:bulk-payment-submit:' . $businessId . ':' . $customerId . ':' . $token;
    }

    private function availableColumns(string $table, array $data): array
    {
        $columns = array_flip(SchemaCache::columns($table));
        return array_intersect_key($data, $columns);
    }

    private function ledgerSubType(string $method): string
    {
        return match ($method) {
            'card' => 'card_payment',
            'cheque' => 'cheque_payment',
            'bank_transfer' => 'cash_deposit',
            default => 'cash_payment',
        };
    }

    private function amount($value): float
    {
        return round((float) str_replace([',', ' '], '', (string) $value), 4);
    }

    private function dateTime($value): string
    {
        try {
            return Carbon::parse($value ?: now())->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['transaction_date' => 'Please enter a valid transaction date.']);
        }
    }

    private function dateOnly($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['cheque_date' => 'Please enter a valid cheque date.']);
        }
    }

    private function normaliseReference(string $reference): string
    {
        $reference = strtoupper(trim($reference));
        if ($reference === '') {
            return '';
        }

        if (!str_starts_with($reference, 'CPB-')) {
            $reference = 'CPB-' . ltrim($reference, '-');
        }

        return preg_replace('/[^A-Z0-9\-]/', '', $reference) ?: '';
    }

    /**
     * IS1813: expose contacts.opening_balance as a selectable invoice when the
     * tenant has no matching opening_balance transaction yet.
     */
    private function withContactOpeningBalance(Collection $invoices, int $businessId, Customer $customer): Collection
    {
        $openingBalance = SchemaCache::hasColumn('contacts', 'opening_balance')
            ? max((float) ($customer->opening_balance ?? 0), 0.0)
            : 0.0;

        if ($openingBalance < 0.005 || !SchemaCache::hasTable('transactions')) {
            return $invoices->values();
        }

        $hasOpeningTransaction = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', (int) $customer->id)
            ->whereIn('type', ['opening_balance', 'fleet_opening_balance'])
            ->when(SchemaCache::hasColumn('transactions', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->exists();

        if ($hasOpeningTransaction || $invoices->contains(function ($row) {
            return !empty($row->is_opening_balance)
                || in_array(strtolower((string) ($row->type ?? '')), ['opening_balance', 'fleet_opening_balance'], true);
        })) {
            return $invoices->values();
        }

        $row = new \stdClass();
        $row->id = -1 * (int) $customer->id;
        $row->transaction_date = SchemaCache::hasColumn('contacts', 'transaction_date') && !empty($customer->transaction_date)
            ? $customer->transaction_date
            : ($customer->created_at ?? Carbon::today()->format('Y-m-d'));
        $row->invoice_no = 'Opening Balance';
        $row->ref_no = '';
        $row->order_no = '';
        $row->type = 'opening_balance';
        $row->payment_status = 'due';
        $row->final_total = $openingBalance;
        $row->invoice_total = $openingBalance;
        $row->total_paid = 0.0;
        $row->outstanding = $openingBalance;
        $row->is_opening_balance = true;
        $row->is_contact_opening_balance = true;

        return collect([$row])->concat($invoices)->values();
    }

    /** Create the real transaction only when the synthetic opening row is paid. */
    private function ensureContactOpeningBalanceTransaction(
        int $businessId,
        int $userId,
        Customer $customer,
        object $invoice
    ): int {
        $existing = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', (int) $customer->id)
            ->whereIn('type', ['opening_balance', 'fleet_opening_balance'])
            ->when(SchemaCache::hasColumn('transactions', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('id')
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $locationId = null;
        if (SchemaCache::hasTable('business_locations')) {
            $locationQuery = DB::table('business_locations')->where('business_id', $businessId);
            if (SchemaCache::hasColumn('business_locations', 'deleted_at')) {
                $locationQuery->whereNull('deleted_at');
            }
            if (SchemaCache::hasColumn('business_locations', 'is_active')) {
                $locationQuery->where(function ($query) {
                    $query->where('is_active', 1)->orWhereNull('is_active');
                });
            }
            $locationId = $locationQuery->orderBy('id')->value('id');
        }

        $transactionDate = !empty($invoice->transaction_date)
            ? Carbon::parse($invoice->transaction_date)->format('Y-m-d')
            : Carbon::today()->format('Y-m-d');
        $amount = max((float) ($invoice->outstanding ?? $invoice->invoice_total ?? 0), 0.0);

        $data = [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'type' => 'opening_balance',
            'status' => 'final',
            'payment_status' => 'due',
            'contact_id' => (int) $customer->id,
            'transaction_date' => $transactionDate,
            'invoice_no' => 'Opening Balance',
            'ref_no' => 'Opening Balance',
            'total_before_tax' => $amount,
            'final_total' => $amount,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $insert = $this->availableColumns('transactions', $data);
        if (empty($insert)) {
            throw ValidationException::withMessages([
                'allocations' => 'The customer Opening Balance transaction could not be prepared.',
            ]);
        }

        return (int) DB::table('transactions')->insertGetId($insert);
    }

    private function invoiceLabel(object $invoice): string
    {
        $type = strtolower((string) ($invoice->type ?? ''));
        if (!empty($invoice->is_opening_balance) || in_array($type, ['opening_balance', 'fleet_opening_balance'], true)) {
            return 'Opening Balance';
        }

        return (string) ($invoice->invoice_no ?: ($invoice->ref_no ?: ('Transaction #' . $invoice->id)));
    }
}
