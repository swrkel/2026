<?php

namespace Modules\MPCS\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MPCS\Http\Controllers\F22FormController;

/**
 * MPCS F22 report entry controller.
 *
 * The active MPCS module has no Modules\\MPCS\\Http\\Controllers\\Controller
 * base class, so this controller extends Laravel's routing controller directly.
 */
class F22ReportController extends Controller
{
    public function index(Request $request)
    {
        return app(F22FormController::class)->F22StockTaking($request);
    }
}
