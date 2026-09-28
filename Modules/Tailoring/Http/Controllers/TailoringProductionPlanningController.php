<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Entities\TailoringProductionPlan;
use Modules\Tailoring\Services\TailoringProductionPlanningService;

class TailoringProductionPlanningController extends Controller
{
    public function index(TailoringProductionPlanningService $service)
    {
        $summary = $service->dashboardSummary(session('business.id'), request('location_id'));
        $plans = TailoringProductionPlan::latest()->paginate(25);
        return view('tailoring::production.planning', compact('summary', 'plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plan_date' => 'required|date',
            'plan_type' => 'required|string|max:20',
            'department_id' => 'nullable|integer',
            'assigned_user_id' => 'nullable|integer',
            'planned_qty' => 'nullable|integer',
            'priority' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);
        $data['business_id'] = session('business.id');
        $data['location_id'] = $request->input('location_id');
        $data['created_by'] = auth()->id();
        TailoringProductionPlan::create($data);
        return redirect()->back()->with('status', 'Production plan saved successfully.');
    }
}
