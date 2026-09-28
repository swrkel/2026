<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Report\SupplierOutstandingReportService;

class SupplierOutstandingReportController extends Controller
{
    public function index()
    {
        return view('purchase::reports.supplier_outstanding.index');
    }

    public function data(Request $request, SupplierOutstandingReportService $service)
    {
        return response()->json($service->data($request->all()));
    }
}
