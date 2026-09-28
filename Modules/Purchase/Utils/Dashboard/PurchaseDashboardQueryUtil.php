<?php

namespace Modules\Purchase\Utils\Dashboard;

use Illuminate\Support\Facades\DB;

class PurchaseDashboardQueryUtil
{
    protected function baseTransactionQuery(array $filters = [])
    {
        $query = DB::table('transactions')
            ->where('business_id', session('user.business_id'))
            ->where('type', 'purchase');

        if (! empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return $query;
    }

    public function summary(array $filters = []): array
    {
        $query = $this->baseTransactionQuery($filters);

        return [
            'purchase_count' => (clone $query)->count(),
            'purchase_total' => (clone $query)->sum('final_total'),
            'paid_total' => (clone $query)->sum('total_before_tax'),
            'outstanding_total' => max(((clone $query)->sum('final_total') - (clone $query)->sum('total_before_tax')), 0),
        ];
    }

    public function outstanding(array $filters = []): array
    {
        return [
            'total' => $this->summary($filters)['outstanding_total'],
        ];
    }

    public function recentPurchases(array $filters = []): array
    {
        return $this->baseTransactionQuery($filters)
            ->select('id', 'transaction_date', 'ref_no', 'final_total', 'payment_status')
            ->latest('id')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
