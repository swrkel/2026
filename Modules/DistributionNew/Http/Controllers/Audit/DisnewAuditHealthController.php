<?php

namespace Modules\DistributionNew\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewHealthCheck;
use Modules\DistributionNew\Services\Health\DisnewHealthCheckService;

class DisnewAuditHealthController extends Controller
{
    public function index()
    {
        $checks = DisnewHealthCheck::latest('id')->limit(100)->get();
        return view('distributionnew::audit.health', compact('checks'));
    }

    public function run(Request $request, DisnewHealthCheckService $service)
    {
        $businessId = (int) ($request->session()->get('user.business_id') ?? $request->input('business_id'));
        $locationId = $request->input('location_id') ? (int) $request->input('location_id') : null;
        $userId = optional($request->user())->id;

        $service->run($businessId, $locationId, $userId);

        return redirect()->back()->with('status', __('distributionnew::lang.health_check_completed'));
    }
}
