<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRManager\Models\HrAttendance;
use Modules\HRManager\Services\HrAttendanceService;

class HrAttendanceController extends Controller
{
    protected HrAttendanceService $attendanceService;

    public function __construct(HrAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $businessId = session('business.id');

        $attendances = HrAttendance::where('business_id', $businessId)
            ->when($request->date_from, fn ($q) => $q->whereDate('attendance_date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('attendance_date', '<=', $request->date_to))
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('hrmanager::attendance.index', compact('attendances'));
    }

    public function kiosk()
    {
        return view('hrmanager::attendance.kiosk');
    }

    public function signIn(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $this->attendanceService->signIn([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'source' => $request->source ?? 'manual',
            'device_name' => $request->device_name,
            'ip_address' => $request->ip(),
            'location_text' => $request->location_text,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Employee signed in successfully.']);
    }

    public function signOut(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $this->attendanceService->signOut([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'source' => $request->source ?? 'manual',
            'device_name' => $request->device_name,
            'ip_address' => $request->ip(),
            'location_text' => $request->location_text,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Employee signed out successfully.']);
    }
}
