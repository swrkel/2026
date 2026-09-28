<?php

namespace Modules\Suppliers\Http\Controllers\Financial;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Financial\SupplierAdvancePaymentService;
use Modules\Suppliers\Utils\SupplierPermissionUtil;

class SupplierAdvancePaymentController extends Controller
{
    protected $supplierAdvancePaymentService;

    public function __construct(SupplierAdvancePaymentService $supplierAdvancePaymentService)
    {
        $this->supplierAdvancePaymentService = $supplierAdvancePaymentService;
    }

    public function index(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        $filters = $request->only(['location_id', 'start_date', 'end_date', 'status', 'payment_status', 'search']);
        $summary = $this->supplierAdvancePaymentService->summary($supplier, $filters);

        return view('suppliers::financial.advances.index', compact('supplier', 'filters', 'summary'));
    }

    public function data(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        return response()->json($this->supplierAdvancePaymentService->datatable($supplier, $request->all()));
    }
}
