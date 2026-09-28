<?php

namespace Modules\Suppliers\Services\Profile;

use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierTransaction;

class SupplierPurchaseSummaryService
{
    public function recentPurchases(Supplier $supplier, int $limit = 10): array
    {
        if (! Schema::hasTable((new SupplierTransaction())->getTable())) {
            return [];
        }

        return SupplierTransaction::query()
            ->select('id', 'ref_no', 'transaction_date', 'final_total', 'payment_status', 'status')
            ->where('business_id', \Modules\Suppliers\Utils\SupplierContextUtil::businessId())
            ->where('contact_id', $supplier->id)
            ->whereIn('type', ['purchase', 'purchase_return'])
            ->orderByDesc('transaction_date')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'ref_no' => $row->ref_no,
                    'transaction_date' => $row->transaction_date ? date('Y-m-d', strtotime($row->transaction_date)) : null,
                    'final_total' => $row->final_total,
                    'payment_status' => $row->payment_status,
                    'status' => $row->status,
                ];
            })
            ->toArray();
    }
}
