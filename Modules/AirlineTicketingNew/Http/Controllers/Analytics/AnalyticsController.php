<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Analytics;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Analytics\SimpleForecastService;

class AnalyticsController extends Controller
{
    public function index(SimpleForecastService $service)
    {
        return view('airlineticketingnew::analytics.index', [
            'forecast' => $service->forecastSales((int) session('business.id')),
        ]);
    }
}
