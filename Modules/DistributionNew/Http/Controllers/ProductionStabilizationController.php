<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\ProductionStabilizationService;

class ProductionStabilizationController extends Controller
{
    protected ProductionStabilizationService $service;

    public function __construct(ProductionStabilizationService $service)
    {
        $this->service = $service;
        $this->middleware(['web', 'auth']);
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id');
        $data = $this->service->dashboard($businessId, $locationId);
        return view('distributionnew::production_stabilization.index', compact('data'));
    }

    public function exceptions(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $items = $this->service->exceptions($businessId, $request->all());
        return view('distributionnew::production_stabilization.exceptions', compact('items'));
    }

    public function audits(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $items = $this->service->audits($businessId, $request->all());
        return view('distributionnew::production_stabilization.audits', compact('items'));
    }

    public function resolveException(Request $request, $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $this->service->resolveException($businessId, (int) $id, $request->get('resolution_note'));
        return redirect()->back()->with('status', __('distributionnew::lang.exception_resolved'));
    }
}
