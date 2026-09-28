<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Report\PurchasePaymentReportService;

class PurchasePaymentReportController extends Controller
{
    public function index()
    {
        return view('purchase::reports.purchase_payment.index');
    }

    public function data(Request $request, PurchasePaymentReportService $service)
    {
        return response()->json($service->data($request->all()));
    }
}
