<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Reporting;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Analytics\BusinessIntelligenceService;

class BiDashboardController extends Controller
{
    public function index(BusinessIntelligenceService $service)
    {
        $businessId = (int) session('business.id');

        return view('airlineticketingnew::reporting.bi-dashboard', [
            'monthlySales' => $service->monthlySales($businessId),
            'yearOverYear' => $service->yearOverYear($businessId),
        ]);
    }
}
