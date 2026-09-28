<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Routing\Controller;

class PurchaseReportController extends Controller
{
    public function index()
    {
        return view('purchase::reports.index');
    }

    public function register()
    {
        return view('purchase::reports.register');
    }

    public function supplierSummary()
    {
        return view('purchase::reports.supplier_summary');
    }

    public function outstanding()
    {
        return view('purchase::reports.outstanding');
    }
}
