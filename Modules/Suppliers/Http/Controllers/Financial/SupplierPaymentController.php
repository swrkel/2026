<?php

namespace Modules\Suppliers\Http\Controllers\Financial;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Financial\SupplierPaymentService;
use Modules\Suppliers\Utils\SupplierPermissionUtil;

class SupplierPaymentController extends Controller
{
    protected $supplierPaymentService;

    public function __construct(SupplierPaymentService $supplierPaymentService)
    {
        $this->supplierPaymentService = $supplierPaymentService;
    }

    public function index(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        $filters = $request->only(['location_id', 'start_date', 'end_date', 'status', 'payment_status', 'search']);
        $summary = $this->supplierPaymentService->summary($supplier, $filters);

        return view('suppliers::financial.payments.index', compact('supplier', 'filters', 'summary'));
    }

    public function data(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        return response()->json($this->supplierPaymentService->datatable($supplier, $request->all()));
    }
}
