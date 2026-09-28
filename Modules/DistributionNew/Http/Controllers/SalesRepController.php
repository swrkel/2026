<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\SalesRep\DisnewSalesRepService;

class SalesRepController extends Controller
{
    public function index(Request $request, DisnewSalesRepService $service)
    {
        $salesReps = $service->listForBusiness($request->session()->get('business.id'), $request->get('business_location_id'));
        return view('distributionnew::sales_reps.index', compact('salesReps'));
    }

    public function store(Request $request, DisnewSalesRepService $service)
    {
        $data = $request->only(['id','user_id','sales_rep_code','sales_rep_name','mobile','business_location_id','default_route_id','default_vehicle_id','is_active']);
        $data['business_id'] = $request->session()->get('business.id');
        $service->save($data);
        return redirect()->back()->with('status', __('distributionnew::messages.saved_successfully'));
    }
}
