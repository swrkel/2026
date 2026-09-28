<?php

namespace Modules\Suppliers\Http\Controllers\Ledger;

use Illuminate\Http\Request;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Services\Ledger\SupplierLedgerQueryService;

class SupplierAgingController extends Controller
{
    protected SupplierLedgerQueryService $ledgerQuery;

    public function __construct(SupplierLedgerQueryService $ledgerQuery)
    {
        $this->ledgerQuery = $ledgerQuery;
    }

    public function index(Request $request, int $supplier)
    {
        abort_unless(\Modules\Suppliers\Utils\SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = (int) \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
        $supplierRecord = $this->ledgerQuery->supplier($businessId, $supplier);
        $filters = $request->only(['location_id', 'as_of_date']);
        $aging = $this->ledgerQuery->aging($businessId, $supplier, $filters);

        return view('suppliers::aging.index', compact('supplierRecord', 'aging', 'filters'));
    }

    public function data(Request $request, int $supplier)
    {
        abort_unless(\Modules\Suppliers\Utils\SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = (int) \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
        return response()->json($this->ledgerQuery->aging($businessId, $supplier, $request->only(['location_id', 'as_of_date'])));
    }
}
