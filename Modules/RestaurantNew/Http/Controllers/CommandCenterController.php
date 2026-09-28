<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\RestaurantCommandCenterService;

class CommandCenterController extends Controller
{
    protected RestaurantCommandCenterService $service;

    public function __construct(RestaurantCommandCenterService $service)
    {
        $this->service = $service;
    }

    public function restaurant(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        return view('restaurantnew::command-center.restaurant', ['summary' => $this->service->restaurantSummary($businessId, $locationId)]);
    }

    public function kitchen(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        return view('restaurantnew::command-center.kitchen', ['summary' => $this->service->kitchenSummary($businessId, $locationId)]);
    }

    public function cashier(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        return view('restaurantnew::command-center.cashier', ['summary' => $this->service->cashierSummary($businessId, $locationId)]);
    }

    public function waiter(Request $request)
    {
        return view('restaurantnew::command-center.waiter');
    }

    public function manager(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        return view('restaurantnew::command-center.manager', ['kpis' => $this->service->managerKpis($businessId, $locationId)]);
    }

    public function executive(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        return view('restaurantnew::command-center.executive', ['summary' => $this->service->restaurantSummary($businessId, null)]);
    }

    public function data(Request $request, string $screen)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id') ? (int) $request->get('location_id') : null;
        return response()->json([
            'restaurant' => $this->service->restaurantSummary($businessId, $locationId),
            'kitchen' => $this->service->kitchenSummary($businessId, $locationId),
            'cashier' => $this->service->cashierSummary($businessId, $locationId),
            'manager' => $this->service->managerKpis($businessId, $locationId),
        ][$screen] ?? []);
    }
}
