<?php

namespace Modules\Suppliers\Http\Controllers\Financial;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Financial\SupplierOutstandingService;
use Modules\Suppliers\Utils\SupplierPermissionUtil;

class SupplierOutstandingController extends Controller
{
    protected $supplierOutstandingService;

    public function __construct(SupplierOutstandingService $supplierOutstandingService)
    {
        $this->supplierOutstandingService = $supplierOutstandingService;
    }

    public function index(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        $filters = $request->only(['location_id', 'start_date', 'end_date', 'status', 'payment_status', 'search']);
        $summary = $this->supplierOutstandingService->summary($supplier, $filters);

        return view('suppliers::financial.outstanding.index', compact('supplier', 'filters', 'summary'));
    }

    public function data(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        return response()->json($this->supplierOutstandingService->datatable($supplier, $request->all()));
    }
}
