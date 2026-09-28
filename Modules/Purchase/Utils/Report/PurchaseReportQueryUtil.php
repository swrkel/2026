<?php

namespace Modules\Purchase\Utils\Report;

use Illuminate\Support\Facades\DB;

class PurchaseReportQueryUtil
{
    public function baseQuery(string $reportType, array $filters)
    {
        $query = DB::table('transactions')
            ->where('transactions.business_id', session('user.business_id'));

        if (in_array($reportType, ['purchase_register', 'purchase_payment', 'product_purchase'])) {
            $query->where('transactions.type', 'purchase');
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('transactions.transaction_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('transactions.transaction_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['location_id'])) {
            $query->where('transactions.location_id', $filters['location_id']);
        }

        return $query->select(
            'transactions.id',
            'transactions.transaction_date',
            'transactions.ref_no',
            'transactions.final_total',
            'transactions.payment_status',
            'transactions.status',
            'transactions.location_id'
        )->orderByDesc('transactions.transaction_date');
    }

    public function totals(string $reportType, array $filters): array
    {
        $query = $this->baseQuery($reportType, $filters);

        return [
            'count' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('transactions.final_total'),
        ];
    }
}
