<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Services\Profile\SupplierPurchaseHistoryPageService;
use Modules\Suppliers\Utils\SupplierProfileAccessUtil;

class SupplierPurchaseHistoryTabController extends Controller
{
    public function index(
        Request $request,
        Supplier $supplier,
        SupplierPurchaseHistoryPageService $historyService
    ) {
        SupplierProfileAccessUtil::ensureTenantSupplier($supplier);

        $filters = $request->only(['search', 'per_page']);
        $purchases = $historyService->paginate($supplier, $filters);

        return view('suppliers::purchase_history.index', compact('supplier', 'purchases', 'filters'));
    }
}
