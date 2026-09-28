<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrManagerPageController extends Controller
{
    public function dashboard() { return view('hrmanager::dashboard.index', $this->payload()); }
    public function employees() { return view('hrmanager::employees.index', $this->payload()); }
    public function setup() { return view('hrmanager::setup.index', $this->payload()); }
    public function attendance() { return view('hrmanager::attendance.index', $this->payload()); }
    public function faceAttendance() { return view('hrmanager::face.index', $this->payload()); }
    public function payroll() { return view('hrmanager::payroll.index', $this->payload()); }

    private function payload(): array
    {
        $businessId = session('business.id');

        return [
            'stats' => [
                'employees' => $this->countTable('hr_employees', $businessId),
                'present_today' => $this->todayAttendance($businessId),
                'on_leave_today' => $this->leaveToday($businessId),
                'absent_today' => 0,
                'pending_leave' => $this->statusCount('hr_leave_requests', $businessId, 'pending'),
                'payroll_month' => $this->payrollTotal($businessId),
                'overtime_hours' => $this->overtimeHours($businessId),
                'birthdays' => 0,
                'departments' => $this->countTable('hr_departments', $businessId),
                'designations' => $this->countTable('hr_designations', $businessId),
                'shifts' => $this->countTable('hr_shifts', $businessId),
                'face_profiles' => $this->countTable('hr_face_profiles', $businessId),
                'face_devices' => $this->countTable('hr_face_devices', $businessId),
                'payroll_periods' => $this->countTable('hr_payroll_periods', $businessId),
                'salary_structures' => $this->countTable('hr_salary_structures', $businessId),
                'payslips' => $this->countTable('hr_payslips', $businessId),
            ],
            'employees' => $this->rows('hr_employees', $businessId, 30),
            'departments' => $this->rows('hr_departments', $businessId, 30),
            'designations' => $this->rows('hr_designations', $businessId, 30),
            'shifts' => $this->rows('hr_shifts', $businessId, 30),
            'attendanceRows' => $this->rows('hr_attendance', $businessId, 30),
            'attendanceLogs' => $this->rows('hr_attendance_logs', $businessId, 30),
            'faceProfiles' => $this->rows('hr_face_profiles', $businessId, 30),
            'faceDevices' => $this->rows('hr_face_devices', $businessId, 30),
            'faceAttempts' => $this->rows('hr_face_attendance_attempts', $businessId, 30),
            'leaveRows' => $this->rows('hr_leave_requests', $businessId, 10),
            'payrollPeriods' => $this->rows('hr_payroll_periods', $businessId, 30),
            'salaryStructures' => $this->rows('hr_salary_structures', $businessId, 30),
            'payrollRuns' => $this->rows('hr_payroll_runs', $businessId, 30),
            'payslips' => $this->rows('hr_payslips', $businessId, 30),
        ];
    }

    private function hasTable(string $table): bool
    {
        try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; }
    }

    private function countTable(string $table, $businessId): int
    {
        return $this->hasTable($table) ? DB::table($table)->where('business_id', $businessId)->count() : 0;
    }

    private function statusCount(string $table, $businessId, string $status): int
    {
        return $this->hasTable($table) ? DB::table($table)->where('business_id', $businessId)->where('status', $status)->count() : 0;
    }

    private function rows(string $table, $businessId, int $limit)
    {
        return $this->hasTable($table) ? DB::table($table)->where('business_id', $businessId)->orderByDesc('id')->limit($limit)->get() : collect();
    }

    private function todayAttendance($businessId): int
    {
        if (!$this->hasTable('hr_attendance')) return 0;
        return DB::table('hr_attendance')->where('business_id', $businessId)->whereDate('attendance_date', now()->toDateString())->where('status', 'present')->count();
    }

    private function leaveToday($businessId): int
    {
        if (!$this->hasTable('hr_leave_requests')) return 0;
        return DB::table('hr_leave_requests')->where('business_id', $businessId)->where('status', 'approved')->whereDate('from_date', '<=', now()->toDateString())->whereDate('to_date', '>=', now()->toDateString())->count();
    }

    private function payrollTotal($businessId): float
    {
        if (!$this->hasTable('hr_payroll_runs')) return 0;
        return (float) DB::table('hr_payroll_runs')->where('business_id', $businessId)->whereMonth('run_date', now()->month)->whereYear('run_date', now()->year)->sum('net_total');
    }

    private function overtimeHours($businessId): float
    {
        if (!$this->hasTable('hr_attendance')) return 0;
        return round(((float) DB::table('hr_attendance')->where('business_id', $businessId)->whereMonth('attendance_date', now()->month)->whereYear('attendance_date', now()->year)->sum('overtime_minutes')) / 60, 2);
    }
}
