<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\SustainabilityService;

class SustainabilityController extends Controller
{
    public function __construct(protected SustainabilityService $service) {}

    public function index()
    {
        return view('hotelmanagement::sustainability.index', [
            'sustainability' => $this->service->dashboard(),
        ]);
    }

    public function goal(Request $request)
    {
        $this->service->goal($request->validate([
            'goal_name' => 'required|string|max:160',
            'category' => 'nullable|string|max:80',
            'target_value' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:40',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Sustainability goal saved successfully.');
    }

    public function wasteLog(Request $request)
    {
        $this->service->wasteLog($request->validate([
            'log_date' => 'nullable|date',
            'department' => 'nullable|string|max:100',
            'waste_type' => 'required|string|max:80',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:40',
            'disposal_method' => 'nullable|string|max:120',
            'cost' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Waste log saved successfully.');
    }

    public function initiative(Request $request)
    {
        $this->service->initiative($request->validate([
            'initiative_no' => 'nullable|string|max:60',
            'title' => 'required|string|max:160',
            'category' => 'nullable|string|max:80',
            'owner_name' => 'nullable|string|max:160',
            'planned_start' => 'nullable|date',
            'planned_end' => 'nullable|date',
            'estimated_saving' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:2000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Sustainability initiative saved successfully.');
    }

    public function initiativeStatus($id, Request $request)
    {
        $this->service->initiativeStatus((int)$id, $request->validate([
            'status' => 'required|string|max:40',
            'remarks' => 'nullable|string|max:2000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Initiative status updated successfully.');
    }
}
