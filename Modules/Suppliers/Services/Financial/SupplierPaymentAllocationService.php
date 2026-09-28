<?php

namespace Modules\Suppliers\Services\Financial;

use Modules\Suppliers\Entities\SupplierFinancialMovement;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Utils\Financial\SupplierFinancialFilterUtil;
use Modules\Suppliers\Utils\SupplierFormatUtil;

class SupplierPaymentAllocationService
{
    public function summary(?Supplier $supplier, array $filters = []): array
    {
        $query = $this->baseQuery($supplier, $filters);

        return [
            'total_records' => (clone $query)->count(),
            'total_amount' => SupplierFormatUtil::money((clone $query)->sum('amount')), 
            'balance_due' => SupplierFormatUtil::money((clone $query)->sum('balance_amount')), 
        ];
    }

    public function datatable(?Supplier $supplier, array $request = []): array
    {
        $perPage = max(10, min((int)($request['per_page'] ?? 25), 100));
        $page = max(1, (int)($request['page'] ?? 1));
        $query = $this->baseQuery($supplier, $request);
        $total = (clone $query)->count();

        $rows = $query->forPage($page, $perPage)->get()->map(function ($row) {
            return [
                'date' => $row->transaction_date,
                'reference_no' => $row->reference_no,
                'description' => $row->description,
                'status' => $row->status,
                'payment_status' => $row->payment_status,
                'amount' => SupplierFormatUtil::money($row->amount),
                'balance' => SupplierFormatUtil::money($row->balance_amount),
                'location_id' => $row->location_id,
            ];
        })->values();

        return [
            'data' => $rows,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    protected function baseQuery(?Supplier $supplier, array $filters)
    {
        $businessId = \Modules\Suppliers\Utils\SupplierContextUtil::businessId();

        $query = SupplierFinancialMovement::query()
            ->where('business_id', $businessId)
            ->where('movement_type', 'allocation')
            ->select([
                'id', 'business_id', 'location_id', 'supplier_id', 'transaction_date',
                'reference_no', 'description', 'status', 'payment_status', 'amount', 'balance_amount',
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($supplier) {
            $query->where('supplier_id', $supplier->id);
        }

        return SupplierFinancialFilterUtil::apply($query, $filters);
    }
}
