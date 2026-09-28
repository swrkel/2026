<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Report\PurchaseSellReportService;

class PurchaseSellReportController extends Controller
{
    public function index()
    {
        return view('purchase::reports.purchase_sell.index');
    }

    public function data(Request $request, PurchaseSellReportService $service)
    {
        return response()->json($service->data($request->all()));
    }
}
