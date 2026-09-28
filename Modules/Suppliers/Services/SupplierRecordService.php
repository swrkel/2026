<?php

namespace Modules\Suppliers\Services;

use Modules\Suppliers\Repositories\SupplierPaymentRepository;
use Modules\Suppliers\Repositories\SupplierRepository;
use Modules\Suppliers\Repositories\SupplierTransactionRepository;

class SupplierRecordService
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

    public function query(int $businessId)
    {
        return $this->suppliers->baseQuery($businessId);
    }

    public function activeDropdown(int $businessId, bool $prependNone = true)
    {
        return $this->suppliers->dropdown($businessId, $prependNone);
    }

    public function selectedDropdown(int $businessId, ?int $supplierId, bool $prependNone = true)
    {
        return $this->suppliers->selectedDropdown($businessId, $supplierId, $prependNone);
    }

    public function supplierGroups(int $businessId)
    {
        return $this->suppliers->groupsDropdown($businessId);
    }

    public function outstandingSummary(int $businessId, ?int $locationId = null)
    {
        return $this->transactions
            ->outstandingSummaryQuery($businessId, $locationId)
            ->get()
            ->keyBy('contact_id');
    }

    public function paymentsQuery(int $businessId, ?int $supplierId = null)
    {
        return $this->payments->supplierPaymentsQuery($businessId, $supplierId);
    }
}
