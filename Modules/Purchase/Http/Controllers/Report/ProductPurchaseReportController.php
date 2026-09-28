<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Report\ProductPurchaseReportService;

class ProductPurchaseReportController extends Controller
{
    public function index()
    {
        return view('purchase::reports.product_purchase.index');
    }

    public function data(Request $request, ProductPurchaseReportService $service)
    {
        return response()->json($service->data($request->all()));
    }
}
