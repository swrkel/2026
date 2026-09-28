<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrEmployee;
use Modules\HRManager\Services\HrAttendanceCentralService;

class HrAttendanceCentralController extends Controller
{
    protected HrAttendanceCentralService $attendanceService;

    public function __construct(HrAttendanceCentralService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $businessId = session('business.id');

        $employees = HrEmployee::where('business_id', $businessId)->where('status', 1)->orderBy('full_name')->get();

        $attendanceRows = $this->rows('hr_attendance', $businessId, 50);
        $summaryRows = $this->rows('hr_attendance_daily_summaries', $businessId, 20);
        $exceptionRows = $this->rows('hr_attendance_exceptions', $businessId, 20);

        $stats = [
            'employees' => $employees->count(),
            'present_today' => $this->countToday('hr_attendance', $businessId, 'present'),
            'open_sessions' => $this->countStatus('hr_attendance_sessions', $businessId, 'open'),
            'exceptions' => $this->countStatus('hr_attendance_exceptions', $businessId, 'open'),
        ];

        return view('hrmanager::attendance.index', compact('employees', 'attendanceRows', 'summaryRows', 'exceptionRows', 'stats'));
    }

    public function signIn(Request $request)
    {
        $request->validate(['employee_id' => 'required|integer']);

        $this->attendanceService->signIn([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'source' => $request->source ?? 'manual',
            'device_name' => $request->device_name,
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Employee signed in successfully.']);
    }

    public function signOut(Request $request)
    {
        $request->validate(['employee_id' => 'required|integer']);

        $this->attendanceService->signOut([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'source' => $request->source ?? 'manual',
            'device_name' => $request->device_name,
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Employee signed out successfully.']);
    }

    private function rows(string $table, $businessId, int $limit)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) return collect();
            return DB::table($table)->where('business_id', $businessId)->orderByDesc('id')->limit($limit)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function countToday(string $table, $businessId, string $status): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) return 0;
            return DB::table($table)->where('business_id', $businessId)->whereDate('attendance_date', now()->toDateString())->where('status', $status)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countStatus(string $table, $businessId, string $status): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) return 0;
            return DB::table($table)->where('business_id', $businessId)->where('status', $status)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
