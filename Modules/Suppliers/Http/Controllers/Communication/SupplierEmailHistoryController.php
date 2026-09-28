<?php

namespace Modules\Suppliers\Http\Controllers\Communication;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Communication\SupplierEmailHistoryService;

class SupplierEmailHistoryController extends Controller
{
    protected SupplierEmailHistoryService $service;

    public function __construct(SupplierEmailHistoryService $service)
    {
        $this->service = $service;
    }

    public function index(Supplier $supplier)
    {
        $emails = $this->service->list($supplier);

        return view('suppliers::communication.emails.index', compact('supplier', 'emails'));
    }
}
