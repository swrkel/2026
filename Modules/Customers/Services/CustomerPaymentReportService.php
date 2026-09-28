<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Modules\Customers\Support\SchemaCache;

class CustomerPaymentReportService
{
    protected CustomerPaymentDuplicateResolver $duplicateResolver;

    public function __construct(?CustomerPaymentDuplicateResolver $duplicateResolver = null)
    {
        $this->duplicateResolver = $duplicateResolver ?: new CustomerPaymentDuplicateResolver();
    }

    /**
     * Customer Payment Report.
     *
     * IS2264:
     * - one logical customer payment must appear only once in this report;
     * - a Bulk Payment can contain several invoice-allocation rows in
     *   transaction_payments, but they share one payment reference and are one
     *   receipt/payment from the user's point of view;
     * - explicit Customers-module payments (for example customer_bulk) must not
     *   disappear merely because the invoice they were allocated to is marked
     *   as a settlement transaction.
     *
     * IS2272:
     * - historical duplicate saves using a collision reference such as
     *   SP2026/0020 + SP2026/0020-1 are collapsed safely at read time;
     * - Date Range and Customer filters use the same query for screen and CSV.
     */
    public function data(int $businessId, int $limit = 500, array $filters = []): array
    {
        $filters = $this->normaliseFilters($filters);
        $customers = $this->customerOptions($businessId);

        if ($businessId <= 0
            || !SchemaCache::hasTable('transaction_payments')
            || !SchemaCache::hasColumn('transaction_payments', 'id')
            || !SchemaCache::hasColumn('transaction_payments', 'business_id')) {
            return $this->emptyResult($customers, $filters);
        }

        $limit = min(max($limit, 1), 5000);
        // Read a little further than the visible limit so an old base/-N pair
        // at the edge of the page can still be recognised and collapsed.
        $scanLimit = min(max($limit * 2, $limit), 10000);

        $hasTransactions = SchemaCache::hasTable('transactions')
            && SchemaCache::hasColumn('transaction_payments', 'transaction_id')
            && SchemaCache::hasColumn('transactions', 'id');
        $hasContacts = SchemaCache::hasTable('contacts')
            && SchemaCache::hasColumn('contacts', 'id');
        $hasPaymentFor = SchemaCache::hasColumn('transaction_payments', 'payment_for');
        $hasDeletedAt = SchemaCache::hasColumn('transaction_payments', 'deleted_at');
        $hasPaidInType = SchemaCache::hasColumn('transaction_payments', 'paid_in_type');
        $hasParentId = SchemaCache::hasColumn('transaction_payments', 'parent_id');
        $hasReference = SchemaCache::hasColumn('transaction_payments', 'payment_ref_no');
        $hasAmount = SchemaCache::hasColumn('transaction_payments', 'amount');
        $hasPaidOn = SchemaCache::hasColumn('transaction_payments', 'paid_on');
        $hasCreatedAt = SchemaCache::hasColumn('transaction_payments', 'created_at');

        $eligibleGroups = DB::table('transaction_payments as grouped_tp');

        if ($hasTransactions) {
            $eligibleGroups->leftJoin(
                'transactions as grouped_transactions',
                'grouped_tp.transaction_id',
                '=',
                'grouped_transactions.id'
            );
        }

        $eligibleContactExpression = $this->contactExpression(
            'grouped_tp',
            $hasTransactions ? 'grouped_transactions' : null,
            $hasPaymentFor
        );

        if ($hasContacts && $eligibleContactExpression !== null) {
            $eligibleGroups->leftJoin('contacts as grouped_contacts', function ($join) use ($eligibleContactExpression) {
                $join->on('grouped_contacts.id', '=', DB::raw($eligibleContactExpression));
            });
        }

        $eligibleGroups->where('grouped_tp.business_id', $businessId);

        if ($hasDeletedAt) {
            $eligibleGroups->whereNull('grouped_tp.deleted_at');
        }

        // Only real customers belong in this report. "both" is a customer and
        // supplier contact and is therefore valid here too.
        if ($hasContacts && $eligibleContactExpression !== null && SchemaCache::hasColumn('contacts', 'type')) {
            $eligibleGroups->whereIn('grouped_contacts.type', ['customer', 'both']);
        }

        // Walk-In settlement takings are not customer payments.
        if ($hasContacts && $eligibleContactExpression !== null && SchemaCache::hasColumn('contacts', 'is_default')) {
            $eligibleGroups->where(function ($query) {
                $query->whereNull('grouped_contacts.is_default')
                    ->orWhere('grouped_contacts.is_default', '!=', 1);
            });
        }

        // IS2272 filters are applied before grouping, so the summary and export
        // always match what is shown on screen.
        if (!empty($filters['customer_id']) && $eligibleContactExpression !== null) {
            $eligibleGroups->whereRaw($eligibleContactExpression . ' = ?', [(int) $filters['customer_id']]);
        }

        $filterDateColumn = $hasPaidOn
            ? 'grouped_tp.paid_on'
            : ($hasCreatedAt ? 'grouped_tp.created_at' : null);
        if ($filterDateColumn && !empty($filters['start_date'])) {
            $eligibleGroups->whereDate($filterDateColumn, '>=', $filters['start_date']);
        }
        if ($filterDateColumn && !empty($filters['end_date'])) {
            $eligibleGroups->whereDate($filterDateColumn, '<=', $filters['end_date']);
        }

        /*
         |------------------------------------------------------------------
         | Settlement exclusion without hiding genuine Bulk Payments.
         |------------------------------------------------------------------
         */
        if ($hasPaidInType) {
            $eligibleGroups->where(function ($query) {
                $query->whereNull('grouped_tp.paid_in_type')
                    ->orWhere('grouped_tp.paid_in_type', '!=', 'settlement');
            });
        }

        if ($hasTransactions && SchemaCache::hasColumn('transactions', 'is_settlement')) {
            $eligibleGroups->where(function ($query) use ($hasPaidInType) {
                $query->whereNull('grouped_transactions.id')
                    ->orWhereNull('grouped_transactions.is_settlement')
                    ->orWhere('grouped_transactions.is_settlement', '!=', 1);

                if ($hasPaidInType) {
                    $query->orWhereIn('grouped_tp.paid_in_type', [
                        'customer_bulk',
                        'customer_page',
                        'customer_simple',
                    ]);
                }
            });
        }

        $groupExpression = $this->paymentGroupExpression(
            'grouped_tp',
            $hasPaidInType,
            $hasParentId,
            $hasReference
        );
        $groupAmountExpression = $hasAmount
            ? 'SUM(COALESCE(grouped_tp.amount, 0))'
            : '0';
        $groupInvoiceExpression = $hasTransactions && SchemaCache::hasColumn('transactions', 'invoice_no')
            ? "GROUP_CONCAT(DISTINCT NULLIF(grouped_transactions.invoice_no, '') ORDER BY NULLIF(grouped_transactions.invoice_no, '') SEPARATOR ', ')"
            : 'NULL';

        $eligibleGroups
            ->selectRaw('MAX(grouped_tp.id) as representative_tp_id')
            ->selectRaw($groupExpression . ' as payment_group_key')
            ->selectRaw($groupAmountExpression . ' as group_amount')
            ->selectRaw($groupInvoiceExpression . ' as group_invoice_no')
            ->groupBy(DB::raw($groupExpression));

        $rowsQuery = DB::table('transaction_payments as tp')
            ->joinSub($eligibleGroups, 'eligible_payment_groups', function ($join) {
                $join->on('eligible_payment_groups.representative_tp_id', '=', 'tp.id');
            });

        if ($hasTransactions) {
            $rowsQuery->leftJoin('transactions', 'tp.transaction_id', '=', 'transactions.id');
        }

        $rowContactExpression = $this->contactExpression(
            'tp',
            $hasTransactions ? 'transactions' : null,
            $hasPaymentFor
        );

        if ($hasContacts && $rowContactExpression !== null) {
            $rowsQuery->leftJoin('contacts', function ($join) use ($rowContactExpression) {
                $join->on('contacts.id', '=', DB::raw($rowContactExpression));
            });
        }

        $paidOnExpression = $hasPaidOn
            ? 'tp.paid_on'
            : ($hasCreatedAt ? 'tp.created_at' : 'NULL');
        $createdAtExpression = $hasCreatedAt ? 'tp.created_at' : $paidOnExpression;
        $paymentReferenceExpression = $hasReference ? 'tp.payment_ref_no' : 'NULL';
        $methodExpression = SchemaCache::hasColumn('transaction_payments', 'method') ? 'tp.method' : "''";
        $noteExpression = SchemaCache::hasColumn('transaction_payments', 'note') ? 'tp.note' : "''";
        $paidInTypeExpression = $hasPaidInType ? "COALESCE(tp.paid_in_type, '')" : "''";
        $chequeNumberExpression = SchemaCache::hasColumn('transaction_payments', 'cheque_number')
            ? "COALESCE(tp.cheque_number, '')"
            : "''";
        $bankNameExpression = SchemaCache::hasColumn('transaction_payments', 'bank_name')
            ? "COALESCE(tp.bank_name, '')"
            : "''";
        $invoiceExpression = 'eligible_payment_groups.group_invoice_no';
        $customerIdExpression = $rowContactExpression !== null ? $rowContactExpression : 'NULL';
        $customerNameExpression = $hasContacts && $rowContactExpression !== null && SchemaCache::hasColumn('contacts', 'name')
            ? 'contacts.name'
            : "''";
        $customerCodeExpression = $hasContacts && $rowContactExpression !== null && SchemaCache::hasColumn('contacts', 'contact_id')
            ? 'contacts.contact_id'
            : "''";

        $rows = $rowsQuery
            ->where('tp.business_id', $businessId)
            ->selectRaw('tp.id as id')
            ->selectRaw($paidOnExpression . ' as paid_on')
            ->selectRaw($createdAtExpression . ' as created_at')
            ->selectRaw($paymentReferenceExpression . ' as payment_ref_no')
            ->selectRaw($methodExpression . ' as method')
            ->selectRaw($paidInTypeExpression . ' as paid_in_type')
            ->selectRaw($chequeNumberExpression . ' as cheque_number')
            ->selectRaw($bankNameExpression . ' as bank_name')
            ->selectRaw($customerIdExpression . ' as customer_id')
            ->selectRaw('eligible_payment_groups.group_amount as amount')
            ->selectRaw($noteExpression . ' as note')
            ->selectRaw($invoiceExpression . ' as invoice_no')
            ->selectRaw($customerNameExpression . ' as customer_name')
            ->selectRaw($customerCodeExpression . ' as customer_code')
            ->orderByDesc('paid_on')
            ->orderByDesc('tp.id')
            ->limit($scanLimit)
            ->get();

        $rows = $this->duplicateResolver->collapse($rows)->take($limit)->values();

        return [
            'rows' => $rows,
            'summary' => [
                'total_records' => $rows->count(),
                'total_amount' => $rows->sum('amount'),
            ],
            'customers' => $customers,
            'filters' => $filters,
        ];
    }

    public function customerOptions(int $businessId): array
    {
        if ($businessId <= 0 || !SchemaCache::hasTable('contacts') || !SchemaCache::hasColumn('contacts', 'id')) {
            return [];
        }

        $query = DB::table('contacts')->where('business_id', $businessId);

        if (SchemaCache::hasColumn('contacts', 'type')) {
            $query->whereIn('type', ['customer', 'both']);
        }
        if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (SchemaCache::hasColumn('contacts', 'is_default')) {
            $query->where(function ($scope) {
                $scope->whereNull('is_default')->orWhere('is_default', '!=', 1);
            });
        }

        $nameExpression = SchemaCache::hasColumn('contacts', 'name') ? 'name' : "CONCAT('Customer #', id)";
        $codeExpression = SchemaCache::hasColumn('contacts', 'contact_id') ? 'contact_id' : 'NULL';

        return $query
            ->selectRaw('id, ' . $nameExpression . ' as customer_name, ' . $codeExpression . ' as customer_code')
            ->orderBy('customer_name')
            ->get()
            ->mapWithKeys(function ($customer) {
                $label = trim((string) ($customer->customer_name ?? 'Customer #' . $customer->id));
                $code = trim((string) ($customer->customer_code ?? ''));
                if ($code !== '') {
                    $label .= ' (' . $code . ')';
                }

                return [(int) $customer->id => $label];
            })
            ->all();
    }

    private function normaliseFilters(array $filters): array
    {
        $customerId = max(0, (int) ($filters['customer_id'] ?? 0));
        $startDate = $this->dateValue($filters['start_date'] ?? null);
        $endDate = $this->dateValue($filters['end_date'] ?? null);

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'customer_id' => $customerId ?: null,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function dateValue($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $parts = array_map('intval', explode('-', $value));
        if (count($parts) !== 3 || !checkdate($parts[1], $parts[2], $parts[0])) {
            return null;
        }

        return $value;
    }

    private function paymentGroupExpression(
        string $alias,
        bool $hasPaidInType,
        bool $hasParentId,
        bool $hasReference
    ): string {
        $parts = ['CASE'];

        if ($hasPaidInType && $hasParentId) {
            $parts[] = "WHEN {$alias}.paid_in_type = 'customer_page' AND {$alias}.parent_id IS NOT NULL"
                . " THEN CONCAT('parent:', {$alias}.parent_id)";
        }

        if ($hasReference) {
            $parts[] = "WHEN {$alias}.payment_ref_no IS NOT NULL AND {$alias}.payment_ref_no <> ''"
                . " THEN CONCAT('reference:', {$alias}.payment_ref_no)";
        }

        $parts[] = "ELSE CONCAT('payment:', {$alias}.id) END";

        return implode(' ', $parts);
    }

    private function contactExpression(string $paymentAlias, ?string $transactionAlias, bool $hasPaymentFor): ?string
    {
        if ($hasPaymentFor && $transactionAlias !== null && SchemaCache::hasColumn('transactions', 'contact_id')) {
            return "COALESCE({$paymentAlias}.payment_for, {$transactionAlias}.contact_id)";
        }

        if ($hasPaymentFor) {
            return "{$paymentAlias}.payment_for";
        }

        if ($transactionAlias !== null && SchemaCache::hasColumn('transactions', 'contact_id')) {
            return "{$transactionAlias}.contact_id";
        }

        return null;
    }

    private function emptyResult(array $customers = [], array $filters = []): array
    {
        return [
            'rows' => collect(),
            'summary' => ['total_amount' => 0, 'total_records' => 0],
            'customers' => $customers,
            'filters' => $filters,
        ];
    }
}
