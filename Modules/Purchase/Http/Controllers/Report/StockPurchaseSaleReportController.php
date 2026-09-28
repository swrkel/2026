<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Report\StockPurchaseSaleReportService;

class StockPurchaseSaleReportController extends Controller
{
    public function index()
    {
        return view('purchase::reports.stock_purchase_sale.index');
    }

    public function data(Request $request, StockPurchaseSaleReportService $service)
    {
        return response()->json($service->data($request->all()));
    }
}
