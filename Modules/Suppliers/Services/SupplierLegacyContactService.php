<?php

namespace Modules\Suppliers\Services;

use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierContactGroup;
use Modules\Suppliers\Entities\SupplierTransaction;
use Modules\Suppliers\Repositories\SupplierPaymentRepository;
use Illuminate\Support\Facades\DB;

class SupplierLegacyContactService
{
    public function query(int $businessId)
    {
        return Supplier::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both']);
    }

    public function activeDropdown(int $businessId, bool $prependNone = true)
    {
        $items = $this->query($businessId)
            ->where('active', 1)
            ->select('id', DB::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' - ', COALESCE(supplier_business_name, ''), '(', contact_id, ')')) AS supplier"))
            ->pluck('supplier', 'id');

        return $prependNone ? $items->prepend(__('lang_v1.none'), '') : $items;
    }

    public function supplierGroups(int $businessId)
    {
        return SupplierContactGroup::forSupplierDropdown($businessId, true);
    }

    public function outstandingSummary(int $businessId, ?int $locationId = null)
    {
        $transactions = SupplierTransaction::query()
            ->where('transactions.business_id', $businessId)
            ->whereIn('transactions.type', ['purchase', 'purchase_return'])
            ->when($locationId, fn ($q) => $q->where('transactions.location_id', $locationId))
            ->whereNotNull('transactions.contact_id')
            ->selectRaw('transactions.contact_id, SUM(final_total) as final_total, SUM(payment_status = "due") as due_count')
            ->groupBy('transactions.contact_id')
            ->get()
            ->keyBy('contact_id');

        return $transactions;
    }

    public function paymentsQuery(int $businessId, ?int $supplierId = null)
    {
        // Keep all compatibility services on the same canonical payment source:
        // root/real payments only, including transaction-less SLP Pay Due rows.
        return (new SupplierPaymentRepository())->supplierPaymentsQuery($businessId, $supplierId);
    }
}
