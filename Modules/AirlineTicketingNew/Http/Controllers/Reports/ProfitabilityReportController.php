<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Reports\Queries\ProfitabilityReportQuery;

class ProfitabilityReportController extends Controller
{
    public function index(Request $request, ProfitabilityReportQuery $query)
    {
        $filters = $request->only(['date_from','date_to']);
        $records = $query->build((int) session('business.id'), $filters)->paginate(50)->withQueryString();

        return view('airlineticketingnew::reports.profitability.index', compact('records','filters'));
    }
}
