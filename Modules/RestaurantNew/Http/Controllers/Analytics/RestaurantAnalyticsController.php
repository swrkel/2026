<?php

namespace Modules\RestaurantNew\Http\Controllers\Analytics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\RestaurantAnalyticsService;

class RestaurantAnalyticsController extends Controller
{
    protected RestaurantAnalyticsService $analytics;

    public function __construct(RestaurantAnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id');
        $data = $this->analytics->dashboard($businessId, $locationId ? (int) $locationId : null, $request->get('from'), $request->get('to'));
        return view('restaurantnew::analytics.index', $data);
    }

    public function forecast(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        $forecast = $this->analytics->generateSimpleForecast($businessId, $locationId, $request->get('forecast_date', now()->addDay()->toDateString()), auth()->id());
        return response()->json(['success' => true, 'forecast' => $forecast]);
    }
}
