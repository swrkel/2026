<?php

namespace Modules\RestaurantNew\Http\Controllers\Ai;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\Ai\RestaurantAiOperationsService;

class RestaurantAiOperationsController extends Controller
{
    protected RestaurantAiOperationsService $ai;

    public function __construct(RestaurantAiOperationsService $ai)
    {
        $this->ai = $ai;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        return view('restaurantnew::ai.command_center', $this->ai->commandCenter($businessId, $locationId));
    }

    public function forecast(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        $forecast = $this->ai->generateDailySalesForecast($businessId, $locationId, $request->get('forecast_date', now()->addDay()->toDateString()), auth()->id());
        return response()->json(['success' => true, 'forecast' => $forecast]);
    }

    public function scanMenuProfit(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        $count = $this->ai->detectMenuProfitAlerts($businessId, $locationId);
        return response()->json(['success' => true, 'recommendations_created' => $count]);
    }
}
