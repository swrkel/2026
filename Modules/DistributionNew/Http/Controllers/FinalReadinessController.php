<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\FinalReadinessCheck;
use Modules\DistributionNew\Services\FinalReadinessService;

class FinalReadinessController extends Controller
{
    public function index(FinalReadinessService $service)
    {
        $businessId = session('business.id');
        $service->seed($businessId);
        $checks = FinalReadinessCheck::where('business_id', $businessId)->orderBy('check_group')->orderBy('check_code')->get();
        return view('distributionnew::final_readiness.index', compact('checks'));
    }

    public function update(Request $request, $id)
    {
        $check = FinalReadinessCheck::findOrFail($id);
        $check->update([
            'status' => $request->input('status', 'passed'),
            'message' => $request->input('message'),
            'checked_by' => auth()->id(),
            'checked_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => __('distributionnew::lang.updated_successfully')]);
    }
}
