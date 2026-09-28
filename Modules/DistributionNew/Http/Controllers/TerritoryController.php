<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Routes\DisnewTerritoryService;

class TerritoryController extends Controller
{
    public function index(Request $request, DisnewTerritoryService $service)
    {
        $territories = $service->listForBusiness($request->session()->get('business.id'), $request->get('business_location_id'));
        return view('distributionnew::territories.index', compact('territories'));
    }

    public function store(Request $request, DisnewTerritoryService $service)
    {
        $data = $request->only(['id','name','code','business_location_id','description','is_active']);
        $data['business_id'] = $request->session()->get('business.id');
        $service->save($data);
        return redirect()->back()->with('status', __('distributionnew::messages.saved_successfully'));
    }
}
