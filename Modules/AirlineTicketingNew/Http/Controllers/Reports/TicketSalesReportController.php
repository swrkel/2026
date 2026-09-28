<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Reports\Exports\CsvExportService;
use Modules\AirlineTicketingNew\Reports\Queries\TicketSalesReportQuery;

class TicketSalesReportController extends Controller
{
    public function index(Request $request, TicketSalesReportQuery $query)
    {
        $filters = $request->only(['date_from','date_to','business_location_id','store_id']);
        $records = $query->build((int) session('business.id'), $filters)->paginate(50)->withQueryString();

        return view('airlineticketingnew::reports.ticket-sales.index', compact('records','filters'));
    }

    public function csv(Request $request, TicketSalesReportQuery $query, CsvExportService $export)
    {
        $rows = $query->build((int) session('business.id'), $request->all())->get();

        return $export->download($rows, [
            'Ticket No' => 'ticket_no',
            'Issue Date' => 'issue_date',
            'Airline' => 'airline_name',
            'Passenger' => 'passenger_name',
            'Currency' => 'currency_code',
            'Grand Total' => 'grand_total',
            'Status' => 'status',
        ], 'airline-ticket-sales.csv');
    }
}
