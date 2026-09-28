<?php

namespace Modules\Suppliers\Services\Ledger;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Repositories\SupplierPaymentRepository;
use Modules\Suppliers\Repositories\SupplierRepository;
use Modules\Suppliers\Repositories\SupplierTransactionRepository;

class SupplierLedgerQueryService
{
    protected SupplierRepository $suppliers;
    protected SupplierTransactionRepository $transactions;
    protected SupplierPaymentRepository $payments;

    public function __construct(
        ?SupplierRepository $suppliers = null,
        ?SupplierTransactionRepository $transactions = null,
        ?SupplierPaymentRepository $payments = null
    ) {
        $this->suppliers = $suppliers ?: new SupplierRepository();
        $this->transactions = $transactions ?: new SupplierTransactionRepository();
        $this->payments = $payments ?: new SupplierPaymentRepository();
    }

    public function supplier(int $businessId, int $supplierId): Supplier
    {
        return $this->suppliers->findForBusiness($businessId, $supplierId);
    }

    public function purchaseQuery(int $businessId, int $supplierId, array $filters = []): Builder
    {
        return $this->transactions->purchaseQuery($businessId, $supplierId, $filters);
    }

    public function paymentQuery(int $businessId, int $supplierId, array $filters = [])
    {
        return $this->payments->paymentQuery($businessId, $supplierId, $filters);
    }

    public function balanceSummary(int $businessId, int $supplierId, array $filters = []): array
    {
        $purchaseTotal = (clone $this->purchaseQuery($businessId, $supplierId, $filters))
            ->whereIn('type', ['purchase', 'opening_balance'])
            ->sum('final_total');

        $returnTotal = (clone $this->purchaseQuery($businessId, $supplierId, $filters))
            ->where('type', 'purchase_return')
            ->sum('final_total');

        $paidTotal = (clone $this->paymentQuery($businessId, $supplierId, $filters))->sum('amount');

        return [
            'purchase_total' => (float) $purchaseTotal,
            'return_total' => (float) $returnTotal,
            'paid_total' => (float) $paidTotal,
            'balance_due' => (float) ($purchaseTotal - $returnTotal - $paidTotal),
        ];
    }

    public function aging(int $businessId, int $supplierId, array $filters = []): array
    {
        $asOf = !empty($filters['as_of_date']) ? Carbon::parse($filters['as_of_date']) : Carbon::today();
        $rows = (clone $this->purchaseQuery($businessId, $supplierId, $filters))
            ->whereIn('type', ['purchase', 'opening_balance'])
            ->get();

        $buckets = ['current' => 0, 'days_1_30' => 0, 'days_31_60' => 0, 'days_61_90' => 0, 'over_90' => 0];

        foreach ($rows as $row) {
            $paid = (float) ($row->payment_lines->sum('amount') ?? 0);
            $balance = max(0, (float) $row->final_total - $paid);

            if ($balance <= 0) {
                continue;
            }

            $age = Carbon::parse($row->transaction_date)->diffInDays($asOf, false);

            if ($age <= 0) {
                $buckets['current'] += $balance;
            } elseif ($age <= 30) {
                $buckets['days_1_30'] += $balance;
            } elseif ($age <= 60) {
                $buckets['days_31_60'] += $balance;
            } elseif ($age <= 90) {
                $buckets['days_61_90'] += $balance;
            } else {
                $buckets['over_90'] += $balance;
            }
        }

        $buckets['total'] = array_sum($buckets);

        return $buckets;
    }
}
