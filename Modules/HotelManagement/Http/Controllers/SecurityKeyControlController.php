<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\SecurityKeyControlService;

class SecurityKeyControlController extends Controller
{
    public function __construct(protected SecurityKeyControlService $service) {}
    public function index() { return view('hotelmanagement::security_key_control.index', ['securityKeyControl' => $this->service->dashboard()]); }
    public function keyCard(Request $request) { $this->service->keyCard($request->validate(['key_no'=>'nullable|string|max:60','room_id'=>'nullable|integer|min:1','reservation_id'=>'nullable|integer|min:1','guest_name'=>'nullable|string|max:160','issued_at'=>'nullable|date','expires_at'=>'nullable|date','status'=>'nullable|string|max:40','remarks'=>'nullable|string|max:1000']), optional($request->user())->id); return back()->with('status','Room key/card record saved successfully.'); }
    public function keyStatus($id, Request $request) { $this->service->keyStatus((int)$id, $request->validate(['status'=>'required|string|max:40','remarks'=>'nullable|string|max:1000']), optional($request->user())->id); return back()->with('status','Key/card status updated successfully.'); }
    public function visitorPass(Request $request) { $this->service->visitorPass($request->validate(['pass_no'=>'nullable|string|max:60','visitor_name'=>'required|string|max:160','mobile'=>'nullable|string|max:40','nic_no'=>'nullable|string|max:80','guest_name'=>'nullable|string|max:160','room_id'=>'nullable|integer|min:1','purpose'=>'nullable|string|max:160','check_in_at'=>'nullable|date','check_out_at'=>'nullable|date','status'=>'nullable|string|max:40','remarks'=>'nullable|string|max:1000']), optional($request->user())->id); return back()->with('status','Visitor pass saved successfully.'); }
    public function visitorStatus($id, Request $request) { $this->service->visitorStatus((int)$id, $request->validate(['status'=>'required|string|max:40','remarks'=>'nullable|string|max:1000']), optional($request->user())->id); return back()->with('status','Visitor pass status updated successfully.'); }
    public function incident(Request $request) { $this->service->incident($request->validate(['incident_no'=>'nullable|string|max:60','incident_date'=>'nullable|date','incident_time'=>'nullable|string|max:20','incident_type'=>'required|string|max:80','severity'=>'nullable|string|max:40','location_reference'=>'nullable|string|max:160','reported_by'=>'nullable|string|max:160','guest_name'=>'nullable|string|max:160','description'=>'nullable|string|max:2000','status'=>'nullable|string|max:40','action_taken'=>'nullable|string|max:2000']), optional($request->user())->id); return back()->with('status','Security incident saved successfully.'); }
    public function incidentStatus($id, Request $request) { $this->service->incidentStatus((int)$id, $request->validate(['status'=>'required|string|max:40','action_taken'=>'nullable|string|max:2000']), optional($request->user())->id); return back()->with('status','Incident status updated successfully.'); }
}
