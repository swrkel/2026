<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Reports;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Reports\Queries\OutstandingReportQuery;

class OutstandingReportController extends Controller
{
    public function index(OutstandingReportQuery $query)
    {
        $records = $query->build((int)session('business.id'))->paginate(50);
        return view('airlineticketingnew::reports.outstanding.index', compact('records'));
    }
}
