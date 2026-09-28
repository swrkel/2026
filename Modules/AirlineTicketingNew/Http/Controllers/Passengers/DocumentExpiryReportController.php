<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Passengers\DocumentExpiryService;

class DocumentExpiryReportController extends Controller
{
    public function index(Request $request, DocumentExpiryService $service)
    {
        $days = max(1, min(365, $request->integer('days', 90)));
        $records = $service->expiring((int) session('business.id'), $days);

        return view('airlineticketingnew::reports.document-expiry.index', compact('records', 'days'));
    }
}
