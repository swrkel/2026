<?php

namespace Modules\Suppliers\Http\Controllers\Ledger;

use Illuminate\Http\Request;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Services\Ledger\SupplierStatementService;

class SupplierStatementController extends Controller
{
    protected SupplierStatementService $statementService;

    public function __construct(SupplierStatementService $statementService)
    {
        $this->statementService = $statementService;
    }

    public function index(Request $request, int $supplier)
    {
        abort_unless(\Modules\Suppliers\Utils\SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = (int) \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
        $filters = $request->only(['start_date', 'end_date', 'location_id']);
        $statement = $this->statementService->build($businessId, $supplier, $filters);

        return view('suppliers::statement.index', compact('statement', 'filters'));
    }

    public function data(Request $request, int $supplier)
    {
        abort_unless(\Modules\Suppliers\Utils\SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = (int) \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
        $statement = $this->statementService->build($businessId, $supplier, $request->only(['start_date', 'end_date', 'location_id']));

        return response()->json([
            'summary' => $statement['summary'],
            'rows' => $statement['rows'],
        ]);
    }
}
