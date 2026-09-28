<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Reports\Queries\AirlineSalesReportQuery;

class AirlineSalesReportController extends Controller
{
    public function index(Request $request, AirlineSalesReportQuery $query)
    {
        $filters = $request->only(['date_from','date_to']);
        $records = $query->build((int)session('business.id'), $filters)->paginate(50)->withQueryString();

        return view('airlineticketingnew::reports.airline-sales.index', compact('records','filters'));
    }
}
