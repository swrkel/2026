<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\AssetEquipmentService;

class AssetEquipmentController extends Controller
{
    public function __construct(protected AssetEquipmentService $service) {}

    public function index()
    {
        return view('hotelmanagement::asset_equipment.index', [
            'assetEquipment' => $this->service->dashboard(),
        ]);
    }

    public function asset(Request $request)
    {
        $this->service->asset($request->validate([
            'asset_code' => 'nullable|string|max:60',
            'asset_name' => 'required|string|max:191',
            'asset_category' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'room_id' => 'nullable|integer',
            'serial_no' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Hotel asset saved successfully.');
    }

    public function assignment(Request $request)
    {
        $this->service->assignment($request->validate([
            'asset_id' => 'required|integer',
            'assigned_to_type' => 'required|string|max:40',
            'assigned_to_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'assigned_date' => 'required|date',
            'return_due_date' => 'nullable|date',
            'condition_out' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Asset assignment recorded successfully.');
    }

    public function inspection(Request $request)
    {
        $this->service->inspection($request->validate([
            'asset_id' => 'required|integer',
            'inspection_date' => 'required|date',
            'condition_status' => 'required|string|max:40',
            'next_inspection_date' => 'nullable|date',
            'maintenance_required' => 'nullable|boolean',
            'estimated_cost' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Asset inspection saved successfully.');
    }

    public function dispose($id, Request $request)
    {
        $this->service->dispose((int) $id, $request->validate([
            'disposal_date' => 'required|date',
            'disposal_value' => 'nullable|numeric|min:0',
            'disposal_reason' => 'required|string|max:191',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Asset disposal recorded successfully.');
    }
}
