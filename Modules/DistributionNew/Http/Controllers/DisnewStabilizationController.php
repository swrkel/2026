<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\DisnewServerStabilizationService;

class DisnewStabilizationController extends Controller
{
    public function __construct(private DisnewServerStabilizationService $service)
    {
    }

    public function index(Request $request)
    {
        $businessId = session('business.id') ?? optional(auth()->user())->business_id;
        $checks = $this->service->latest($businessId);

        return view('distributionnew::stabilization.index', compact('checks'));
    }

    public function checklist()
    {
        return view('distributionnew::stabilization.checklist');
    }

    public function runChecks(Request $request)
    {
        $businessId = session('business.id') ?? optional(auth()->user())->business_id;
        $locationId = session('business_location.id') ?? null;
        $userId = optional(auth()->user())->id;

        $checks = $this->service->run($businessId, $locationId, $userId);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'checks' => $checks]);
        }

        return redirect()->route('distribution-new.stabilization.index')
            ->with('status', ['success' => 1, 'msg' => __('distributionnew::lang.stabilization_completed')]);
    }
}
