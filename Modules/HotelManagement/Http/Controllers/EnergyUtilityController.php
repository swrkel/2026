<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\EnergyUtilityService;

class EnergyUtilityController extends Controller
{
    public function __construct(protected EnergyUtilityService $service) {}
    public function index() { return view('hotelmanagement::energy_utility.index', ['energyUtility' => $this->service->dashboard()]); }
    public function meter(Request $request) { $this->service->meter($request->validate(['meter_no'=>'nullable|string|max:60','meter_name'=>'required|string|max:160','utility_type'=>'required|string|max:60','department'=>'nullable|string|max:100','linked_room_id'=>'nullable|integer|min:1','unit_name'=>'nullable|string|max:40','rate_per_unit'=>'nullable|numeric|min:0','is_active'=>'nullable|boolean','remarks'=>'nullable|string|max:1000']), optional($request->user())->id); return back()->with('status','Utility meter saved successfully.'); }
    public function reading(Request $request) { $this->service->reading($request->validate(['reading_no'=>'nullable|string|max:60','meter_id'=>'required|integer|min:1','reading_date'=>'nullable|date','previous_reading'=>'required|numeric|min:0','current_reading'=>'required|numeric|min:0','rate_per_unit'=>'nullable|numeric|min:0','status'=>'nullable|string|max:40','remarks'=>'nullable|string|max:1000']), optional($request->user())->id); return back()->with('status','Utility reading saved successfully.'); }
    public function allocation(Request $request) { $this->service->allocation($request->validate(['allocation_no'=>'nullable|string|max:60','reading_id'=>'nullable|integer|min:1','allocation_type'=>'required|string|max:60','target_reference'=>'nullable|string|max:160','allocated_amount'=>'required|numeric|min:0','status'=>'nullable|string|max:40','remarks'=>'nullable|string|max:1000']), optional($request->user())->id); return back()->with('status','Utility allocation saved successfully.'); }
    public function postAllocation($id, Request $request) { $this->service->postAllocation((int)$id, optional($request->user())->id); return back()->with('status','Utility allocation posted successfully.'); }
}
