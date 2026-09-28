<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\PreventiveMaintenanceService;

class PreventiveMaintenanceController extends Controller
{
    public function __construct(protected PreventiveMaintenanceService $service) {}

    public function index()
    {
        return view('hotelmanagement::preventive_maintenance.index', [
            'pm' => $this->service->dashboard(),
        ]);
    }

    public function plan(Request $request)
    {
        $this->service->plan($request->validate([
            'plan_no' => 'nullable|string|max:60',
            'plan_name' => 'required|string|max:191',
            'asset_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'department' => 'nullable|string|max:100',
            'frequency_type' => 'required|string|max:30',
            'frequency_value' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'next_due_date' => 'nullable|date',
            'priority' => 'nullable|string|max:30',
            'estimated_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
            'instructions' => 'nullable|string|max:2000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Preventive maintenance plan saved successfully.');
    }

    public function generateTask($planId, Request $request)
    {
        $this->service->generateTask((int)$planId, $request->validate([
            'task_title' => 'nullable|string|max:191',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:30',
            'estimated_cost' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Preventive maintenance task generated successfully.');
    }

    public function checklist(Request $request)
    {
        $this->service->checklist($request->validate([
            'plan_id' => 'required|integer',
            'check_item' => 'required|string|max:191',
            'required_result' => 'nullable|string|max:191',
            'sort_order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
        ]), optional($request->user())->id);
        return back()->with('status', 'Checklist item saved successfully.');
    }

    public function status($taskId, Request $request)
    {
        $this->service->status((int)$taskId, $request->validate([
            'status' => 'required|string|max:30',
            'assigned_to' => 'nullable|string|max:100',
            'completed_date' => 'nullable|date',
            'next_due_date' => 'nullable|date',
            'actual_cost' => 'nullable|numeric|min:0',
            'checklist_verified' => 'nullable|boolean',
            'completion_notes' => 'nullable|string|max:2000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Preventive maintenance task updated successfully.');
    }
}
