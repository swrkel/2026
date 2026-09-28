<?php

namespace Modules\Suppliers\Http\Controllers\Communication;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Communication\SupplierCommunicationLogService;

class SupplierCommunicationLogController extends Controller
{
    protected SupplierCommunicationLogService $service;

    public function __construct(SupplierCommunicationLogService $service)
    {
        $this->service = $service;
    }

    public function index(Supplier $supplier)
    {
        $logs = $this->service->list($supplier);

        return view('suppliers::communication.logs.index', compact('supplier', 'logs'));
    }

    public function store(Request $request, Supplier $supplier)
    {
        $this->service->store($supplier, $request->all());

        return redirect()->route('suppliers.communication.logs.index', $supplier)
            ->with('status', __('suppliers::lang.communication_log_saved'));
    }
}
