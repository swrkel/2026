<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Report\PurchaseRegisterReportService;

class PurchaseRegisterReportController extends Controller
{
    public function index()
    {
        return view('purchase::reports.purchase_register.index');
    }

    public function data(Request $request, PurchaseRegisterReportService $service)
    {
        return response()->json($service->data($request->all()));
    }
}
