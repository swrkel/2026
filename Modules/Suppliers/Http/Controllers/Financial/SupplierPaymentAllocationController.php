<?php

namespace Modules\Suppliers\Http\Controllers\Financial;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Financial\SupplierPaymentAllocationService;
use Modules\Suppliers\Utils\SupplierPermissionUtil;

class SupplierPaymentAllocationController extends Controller
{
    protected $supplierPaymentAllocationService;

    public function __construct(SupplierPaymentAllocationService $supplierPaymentAllocationService)
    {
        $this->supplierPaymentAllocationService = $supplierPaymentAllocationService;
    }

    public function index(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        $filters = $request->only(['location_id', 'start_date', 'end_date', 'status', 'payment_status', 'search']);
        $summary = $this->supplierPaymentAllocationService->summary($supplier, $filters);

        return view('suppliers::financial.allocations.index', compact('supplier', 'filters', 'summary'));
    }

    public function data(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        return response()->json($this->supplierPaymentAllocationService->datatable($supplier, $request->all()));
    }
}
