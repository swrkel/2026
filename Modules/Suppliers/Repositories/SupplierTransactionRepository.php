<?php

namespace Modules\Suppliers\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Suppliers\Entities\SupplierTransaction;
use Modules\Suppliers\Utils\SupplierDatabaseUtil;

class SupplierTransactionRepository
{
    public function purchaseQuery(int $businessId, int $supplierId, array $filters = []): Builder
    {
        $query = SupplierTransaction::query()
            ->where('business_id', $businessId)
            ->where('contact_id', $supplierId)
            ->whereIn('type', ['purchase', 'purchase_return', 'opening_balance'])
            ->with(['location:id,name', 'payment_lines']);

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        return $query->orderBy('transaction_date')->orderBy('id');
    }

    public function outstandingSummaryQuery(int $businessId, ?int $locationId = null): Builder
    {
        return SupplierTransaction::query()
            ->where('transactions.business_id', $businessId)
            ->whereIn('transactions.type', ['purchase', 'purchase_return'])
            ->when($locationId, fn ($q) => $q->where('transactions.location_id', $locationId))
            ->whereNotNull('transactions.contact_id')
            ->selectRaw('transactions.contact_id, SUM(final_total) as final_total, SUM(payment_status = "due") as due_count')
            ->groupBy('transactions.contact_id');
    }

    public function sumFinalTotal(Builder $query): float
    {
        return (float) $query->sum(SupplierDatabaseUtil::raw('final_total'));
    }
}
