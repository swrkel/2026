<?php

namespace Modules\Suppliers\Http\Controllers\Financial;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Financial\SupplierPurchaseHistoryService;
use Modules\Suppliers\Utils\SupplierPermissionUtil;

class SupplierPurchaseHistoryController extends Controller
{
    protected $supplierPurchaseHistoryService;

    public function __construct(SupplierPurchaseHistoryService $supplierPurchaseHistoryService)
    {
        $this->supplierPurchaseHistoryService = $supplierPurchaseHistoryService;
    }

    public function index(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        $filters = $request->only(['location_id', 'start_date', 'end_date', 'status', 'payment_status', 'search']);
        $summary = $this->supplierPurchaseHistoryService->summary($supplier, $filters);

        return view('suppliers::financial.purchase_history.index', compact('supplier', 'filters', 'summary'));
    }

    public function data(Request $request, Supplier $supplier = null)
    {
        SupplierPermissionUtil::authorize('supplier.view');

        return response()->json($this->supplierPurchaseHistoryService->datatable($supplier, $request->all()));
    }
}
