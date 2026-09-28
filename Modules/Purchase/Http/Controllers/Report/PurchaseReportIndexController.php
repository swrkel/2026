<?php

namespace Modules\Purchase\Http\Controllers\Report;

use Illuminate\Routing\Controller;

class PurchaseReportIndexController extends Controller
{
    public function index()
    {
        return view('purchase::reports.index');
    }
}
