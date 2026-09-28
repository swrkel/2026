<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\StaffRosteringService;

class StaffRosteringController extends Controller
{
    public function __construct(protected StaffRosteringService $service) {}

    public function index()
    {
        return view('hotelmanagement::staff_roster.index', [
            'staffRoster' => $this->service->dashboard(),
        ]);
    }

    public function role(Request $request)
    {
        $this->service->role($request->validate([
            'role_name' => 'required|string|max:120',
            'department' => 'nullable|string|max:100',
            'standard_hours' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Staff role saved successfully.');
    }

    public function staff(Request $request)
    {
        $this->service->staff($request->validate([
            'employee_no' => 'nullable|string|max:60',
            'name' => 'required|string|max:160',
            'mobile' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160',
            'department' => 'nullable|string|max:100',
            'role_id' => 'nullable|integer',
            'status' => 'nullable|string|max:40',
        ]), optional($request->user())->id);
        return back()->with('status', 'Hotel staff member saved successfully.');
    }

    public function shift(Request $request)
    {
        $this->service->shift($request->validate([
            'shift_no' => 'nullable|string|max:60',
            'staff_id' => 'required|integer',
            'shift_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'department' => 'nullable|string|max:100',
            'station' => 'nullable|string|max:120',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Roster shift saved successfully.');
    }

    public function attendance(Request $request)
    {
        $this->service->attendance($request->validate([
            'staff_id' => 'required|integer',
            'attendance_date' => 'required|date',
            'clock_in' => 'nullable',
            'clock_out' => 'nullable',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Attendance log saved successfully.');
    }

    public function shiftStatus($id, Request $request)
    {
        $this->service->shiftStatus((int)$id, $request->validate([
            'status' => 'required|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Roster shift status updated successfully.');
    }
}
