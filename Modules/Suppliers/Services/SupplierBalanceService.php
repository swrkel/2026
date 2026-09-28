<?php

namespace Modules\Suppliers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Utils\SupplierContextUtil;

/**
 * Calculates the supplier payable balance for supplier-list rows in one batch.
 *
 * The rules intentionally match the established supplier ledger/contact-list
 * calculation so Total Due remains consistent across the ERP.
 */
class SupplierBalanceService
{
    private const TRANSACTION_TYPES = [
        'cheque_return',
        'property_purchase',
        'expense',
        'opening_balance',
        'purchase',
        'purchase_return',
        '_deleted_purchase',
        'ledger',
        'sell_return',
    ];

    private const BALANCE_STATUSES = ['final', 'received', 'ordered', 'pending'];

    private const EXCLUDED_PAYMENT_TRANSACTION_TYPES = [
        'security_deposit',
        'refund_security_deposit',
        'security_deposit_refund',
        'cheque_opening_balance',
    ];

    /**
     * @param  array<int|string>  $supplierIds
     * @return array<int, array<string, float>>
     */
    public function balances(array $supplierIds, ?int $businessId = null): array
    {
        $supplierIds = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $supplierIds
        ))));

        if ($supplierIds === []) {
            return [];
        }

        $businessId = $businessId ?: SupplierContextUtil::businessId();
        $balances = $this->emptyBalances($supplierIds);

        try {
            $openingBalanceTransactionIds = $this->loadTransactionTotals($balances, $supplierIds, $businessId);
            $this->loadContactOpeningBalanceFallback(
                $balances,
                $supplierIds,
                $businessId,
                $openingBalanceTransactionIds
            );
            $this->loadPaymentTotals($balances, $supplierIds, $businessId);
            $this->loadPurchaseReturnPayments($balances, $supplierIds, $businessId);

            foreach ($balances as &$details) {
                $details['return_due'] = $details['total_purchase_return'] - $details['purchase_return_paid'];
                $details['total_due'] = $details['total_purchase']
                    + $details['opening_balance']
                    - $details['purchase_paid'];
            }
            unset($details);
        } catch (\Throwable $exception) {
            Log::error('Suppliers module failed to calculate supplier list balances.', [
                'business_id' => $businessId,
                'supplier_ids' => $supplierIds,
                'error' => $exception->getMessage(),
            ]);
        }

        return $balances;
    }

    /**
     * Calculate the supplier-list financial summary across every matching
     * supplier, independently of pagination. Positive balances contribute to
     * Total Due. Negative balances are converted to a positive value and
     * contribute to Total Overpaid.
     *
     * @param  array<int|string>  $supplierIds
     * @return array{total_due: float, total_overpaid: float}
     */
    public function summary(array $supplierIds, ?int $businessId = null): array
    {
        $supplierIds = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $supplierIds
        ))));

        $summary = [
            'total_due' => 0.0,
            'total_overpaid' => 0.0,
        ];

        if ($supplierIds === []) {
            return $summary;
        }

        // Keep each balance query within a safe parameter count while
        // preserving the exact same balance rules used by the list rows.
        foreach (array_chunk($supplierIds, 1000) as $supplierIdChunk) {
            foreach ($this->balances($supplierIdChunk, $businessId) as $details) {
                $due = (float) ($details['total_due'] ?? 0);

                if ($due > 0) {
                    $summary['total_due'] += $due;
                } elseif ($due < 0) {
                    $summary['total_overpaid'] += abs($due);
                }
            }
        }

        $summary['total_due'] = round($summary['total_due'], 6);
        $summary['total_overpaid'] = round($summary['total_overpaid'], 6);

        return $summary;
    }

    /**
     * @param  array<int>  $supplierIds
     * @return array<int, array<string, float>>
     */
    private function emptyBalances(array $supplierIds): array
    {
        $balances = [];

        foreach ($supplierIds as $supplierId) {
            $balances[$supplierId] = [
                'opening_balance' => 0.0,
                'total_purchase' => 0.0,
                'purchase_paid' => 0.0,
                'total_purchase_return' => 0.0,
                'purchase_return_paid' => 0.0,
                'return_due' => 0.0,
                'total_due' => 0.0,
            ];
        }

        return $balances;
    }

    /**
     * @param  array<int, array<string, float>>  $balances
     * @param  array<int>  $supplierIds
     */
    private function loadTransactionTotals(array &$balances, array $supplierIds, int $businessId): array
    {
        if (!Schema::hasTable('transactions')) {
            return [];
        }

        $openingBalanceTransactionIds = [];

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->whereIn('contact_id', $supplierIds)
            ->whereIn('type', self::TRANSACTION_TYPES);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereIn('status', self::BALANCE_STATUSES);
        }

        $rows = $query
            ->select('contact_id')
            ->selectRaw("SUM(CASE WHEN type = 'cheque_return' THEN COALESCE(final_total, 0) ELSE 0 END) AS cheque_return")
            ->selectRaw("SUM(CASE WHEN type = 'property_purchase' THEN COALESCE(final_total, 0) ELSE 0 END) AS property_purchase")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN COALESCE(final_total, 0) ELSE 0 END) AS expense")
            ->selectRaw("SUM(CASE WHEN type = 'purchase' THEN COALESCE(final_total, 0) ELSE 0 END) AS purchase")
            ->selectRaw("SUM(CASE WHEN type = '_deleted_purchase' THEN COALESCE(final_total, 0) ELSE 0 END) AS purchase_deleted")
            ->selectRaw("SUM(CASE WHEN type = 'opening_balance' THEN COALESCE(final_total, 0) ELSE 0 END) AS opening_balance")
            ->selectRaw("SUM(CASE WHEN type = 'opening_balance' THEN 1 ELSE 0 END) AS opening_balance_count")
            ->selectRaw("SUM(CASE WHEN type = 'purchase_return' THEN COALESCE(final_total, 0) ELSE 0 END) AS purchase_return")
            ->selectRaw("SUM(CASE WHEN type = 'ledger_discount' THEN COALESCE(final_total, 0) ELSE 0 END) AS ledger_discount")
            ->groupBy('contact_id')
            ->get();

        foreach ($rows as $row) {
            $supplierId = (int) $row->contact_id;
            if (!isset($balances[$supplierId])) {
                continue;
            }

            $balances[$supplierId]['opening_balance'] = (float) ($row->opening_balance ?? 0);

            if ((int) ($row->opening_balance_count ?? 0) > 0) {
                $openingBalanceTransactionIds[] = $supplierId;
            }
            $balances[$supplierId]['total_purchase_return'] = (float) ($row->purchase_return ?? 0);
            $balances[$supplierId]['total_purchase'] = (float) (
                ($row->cheque_return ?? 0)
                + ($row->property_purchase ?? 0)
                + ($row->expense ?? 0)
                + ($row->purchase ?? 0)
                - ($row->purchase_deleted ?? 0)
                - ($row->purchase_return ?? 0)
                - ($row->ledger_discount ?? 0)
            );
        }

        return array_values(array_unique($openingBalanceTransactionIds));
    }

    /**
     * Older and standalone supplier screens save the opening balance directly
     * on contacts.opening_balance. Use that value only when the supplier does
     * not already have an opening_balance transaction, preventing double-counts
     * while keeping newly created suppliers visible in Total Due immediately.
     *
     * @param  array<int, array<string, float>>  $balances
     * @param  array<int>  $supplierIds
     * @param  array<int>  $openingBalanceTransactionIds
     */
    private function loadContactOpeningBalanceFallback(
        array &$balances,
        array $supplierIds,
        int $businessId,
        array $openingBalanceTransactionIds
    ): void {
        if (!Schema::hasTable('contacts') || !Schema::hasColumn('contacts', 'opening_balance')) {
            return;
        }

        $transactionIdLookup = array_fill_keys($openingBalanceTransactionIds, true);

        $rows = DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('id', $supplierIds)
            ->select('id', 'opening_balance')
            ->get();

        foreach ($rows as $row) {
            $supplierId = (int) $row->id;

            if (!isset($balances[$supplierId]) || isset($transactionIdLookup[$supplierId])) {
                continue;
            }

            $balances[$supplierId]['opening_balance'] = (float) ($row->opening_balance ?? 0);
        }
    }

    /**
     * @param  array<int, array<string, float>>  $balances
     * @param  array<int>  $supplierIds
     */
    private function loadPaymentTotals(array &$balances, array $supplierIds, int $businessId): void
    {
        if (!Schema::hasTable('transaction_payments') || !Schema::hasTable('transactions')) {
            return;
        }

        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->whereIn('tp.payment_for', $supplierIds);

        if (Schema::hasColumn('transaction_payments', 'business_id')) {
            $query->where('tp.business_id', $businessId);
        } else {
            // Older schemas without transaction_payments.business_id cannot safely
            // identify standalone payments by tenant. Restrict the fallback to the
            // joined transaction business to prevent cross-business totals.
            $query->where('t.business_id', $businessId);
        }

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('tp.deleted_at');
        }

        if (Schema::hasColumn('transaction_payments', 'parent_id')) {
            $query->whereNull('tp.parent_id');
        }

        $query->where(function ($paymentQuery) {
            $paymentQuery->whereNull('tp.transaction_id')
                ->orWhereNotIn('t.type', self::EXCLUDED_PAYMENT_TRANSACTION_TYPES);
        });

        $amountExpression = Schema::hasColumn('transaction_payments', 'is_return')
            ? 'SUM(CASE WHEN COALESCE(tp.is_return, 0) = 0 THEN COALESCE(tp.amount, 0) ELSE -COALESCE(tp.amount, 0) END)'
            : 'SUM(COALESCE(tp.amount, 0))';

        $rows = $query
            ->selectRaw('tp.payment_for AS contact_id')
            ->selectRaw($amountExpression . ' AS total_paid')
            ->groupBy('tp.payment_for')
            ->get();

        foreach ($rows as $row) {
            $supplierId = (int) $row->contact_id;
            if (isset($balances[$supplierId])) {
                $balances[$supplierId]['purchase_paid'] = (float) ($row->total_paid ?? 0);
            }
        }
    }

    /**
     * @param  array<int, array<string, float>>  $balances
     * @param  array<int>  $supplierIds
     */
    private function loadPurchaseReturnPayments(array &$balances, array $supplierIds, int $businessId): void
    {
        if (!Schema::hasTable('transaction_payments') || !Schema::hasTable('transactions')) {
            return;
        }

        $query = DB::table('transaction_payments as tp')
            ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'purchase_return')
            ->whereIn('t.contact_id', $supplierIds);

        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('tp.deleted_at');
        }

        $rows = $query
            ->select('t.contact_id')
            ->selectRaw('SUM(COALESCE(tp.amount, 0)) AS purchase_return_paid')
            ->groupBy('t.contact_id')
            ->get();

        foreach ($rows as $row) {
            $supplierId = (int) $row->contact_id;
            if (isset($balances[$supplierId])) {
                $balances[$supplierId]['purchase_return_paid'] = (float) ($row->purchase_return_paid ?? 0);
            }
        }
    }
}
