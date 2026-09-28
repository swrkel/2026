<?php

namespace Modules\DistributionNew\Http\Controllers\ProductionCompletion;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\ProductionCompletion\PerformanceCacheService;

class ManagementDashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('distributionnew::production_completion.managementdashboard.index');
    }

    public function store(Request $request, PerformanceCacheService $service)
    {
        $businessId = (int) session('business.id');
        $locationId = $request->input('location_id');
        $userId = auth()->id();

        if (method_exists($service, 'run')) {
            return response()->json($service->run($businessId, $locationId ? (int) $locationId : null, $userId));
        }

        return response()->json(['success' => true, 'message' => __('distributionnew::production_completion.saved_successfully')]);
    }
}
