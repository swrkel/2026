<?php

namespace Modules\Suppliers\Http\Controllers\Communication;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Communication\SupplierCommunicationAuditService;

class SupplierCommunicationAuditController extends Controller
{
    protected SupplierCommunicationAuditService $service;

    public function __construct(SupplierCommunicationAuditService $service)
    {
        $this->service = $service;
    }

    public function index(Supplier $supplier)
    {
        $audits = $this->service->list($supplier);

        return view('suppliers::communication.audit.index', compact('supplier', 'audits'));
    }
}
