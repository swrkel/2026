<?php

namespace Modules\DistributionNew\Http\Controllers\FinalReadiness;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\FinalReadiness\FinalReadinessService;

class FinalReadinessController extends Controller
{
    public function index(Request $request, FinalReadinessService $service)
    {
        $businessId = (int) session('business.id', $request->get('business_id'));
        $checks = $service->checks($businessId);
        return view('distributionnew::final_readiness.index', compact('checks'));
    }

    public function run(Request $request, FinalReadinessService $service)
    {
        $businessId = (int) session('business.id', $request->get('business_id'));
        $service->recordRun($businessId, auth()->id(), $request->input('remarks'));
        return redirect()->back()->with('status', __('distributionnew::lang.final_readiness_completed'));
    }
}
