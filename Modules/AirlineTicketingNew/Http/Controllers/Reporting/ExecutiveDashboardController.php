<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Reporting;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Reporting\ExecutiveDashboardService;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request, ExecutiveDashboardService $service)
    {
        $filters = $request->only(['date_from','date_to']);

        return view('airlineticketingnew::reporting.executive-dashboard', [
            'metrics' => $service->metrics((int) session('business.id'), $filters),
            'filters' => $filters,
        ]);
    }
}
