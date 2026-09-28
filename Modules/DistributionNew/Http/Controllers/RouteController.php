<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Routes\DisnewRouteService;
use Modules\DistributionNew\Services\Routes\DisnewTerritoryService;

class RouteController extends Controller
{
    public function index(Request $request, DisnewRouteService $service, DisnewTerritoryService $territoryService)
    {
        $routes = $service->listForBusiness($request->session()->get('business.id'), $request->get('business_location_id'));
        $territories = $territoryService->listForBusiness($request->session()->get('business.id'));
        return view('distributionnew::routes.index', compact('routes', 'territories'));
    }

    public function store(Request $request, DisnewRouteService $service)
    {
        $data = $request->only(['id','route_code','route_name','disnew_territory_id','business_location_id','default_vehicle_id','default_sales_rep_id','visit_day','is_active']);
        $data['business_id'] = $request->session()->get('business.id');
        $service->save($data);
        return redirect()->back()->with('status', __('distributionnew::messages.saved_successfully'));
    }

    public function assignCustomers(Request $request, $route)
    {
        app(DisnewRouteService::class)->assignCustomers((int) $route, $request->input('customer_ids', []));
        return redirect()->back()->with('status', __('distributionnew::messages.customers_assigned'));
    }
}
