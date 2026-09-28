<?php

namespace Modules\ProductsNew\Http\Controllers\Reports;

use Illuminate\Routing\Controller;

class ReportCentreController extends Controller
{
    /**
     * Backward-compatible entry point for older cached routes.
     */
    public function index()
    {
        return redirect()->route('products-new.reports.index');
    }
}
