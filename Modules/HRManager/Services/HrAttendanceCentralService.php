<?php

namespace Modules\HRManager\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrAttendance;
use Modules\HRManager\Models\HrAttendanceLog;
use Modules\HRManager\Models\HrAttendanceSession;
use Modules\HRManager\Models\HrAttendanceDailySummary;

class HrAttendanceCentralService
{
    public function signIn(array $data): HrAttendance
    {
        return DB::transaction(function () use ($data) {
            $logTime = Carbon::parse($data['log_time'] ?? now());
            $date = $logTime->toDateString();

            $attendance = HrAttendance::firstOrCreate(
                [
                    'business_id' => $data['business_id'],
                    'employee_id' => $data['employee_id'],
                    'attendance_date' => $date,
                ],
                [
                    'status' => 'present',
                    'approval_status' => 'approved',
                    'sign_in_source' => $data['source'] ?? 'manual',
                    'created_by' => $data['user_id'] ?? null,
                ]
            );

            if (!$attendance->sign_in_at) {
                $attendance->sign_in_at = $logTime;
                $attendance->sign_in_source = $data['source'] ?? 'manual';
                $attendance->sign_in_device = $data['device_name'] ?? null;
                $attendance->sign_in_ip = $data['ip_address'] ?? null;
                $attendance->updated_by = $data['user_id'] ?? null;
                $attendance->save();
            }

            $session = HrAttendanceSession::create([
                'business_id' => $data['business_id'],
                'employee_id' => $data['employee_id'],
                'attendance_id' => $attendance->id,
                'session_code' => 'ATT-' . now()->format('YmdHis') . '-' . $data['employee_id'],
                'attendance_date' => $date,
                'session_type' => 'regular',
                'source' => $data['source'] ?? 'manual',
                'device_name' => $data['device_name'] ?? null,
                'started_at' => $logTime,
                'status' => 'open',
                'created_by' => $data['user_id'] ?? null,
            ]);

            $this->log($attendance, 'sign_in', $logTime, $data);
            $this->updateDailySummary($attendance);

            return $attendance;
        });
    }

    public function signOut(array $data): HrAttendance
    {
        return DB::transaction(function () use ($data) {
            $logTime = Carbon::parse($data['log_time'] ?? now());
            $date = $logTime->toDateString();

            $attendance = HrAttendance::where('business_id', $data['business_id'])
                ->where('employee_id', $data['employee_id'])
                ->where('attendance_date', $date)
                ->firstOrFail();

            $attendance->sign_out_at = $logTime;
            $attendance->sign_out_source = $data['source'] ?? 'manual';
            $attendance->sign_out_device = $data['device_name'] ?? null;
            $attendance->sign_out_ip = $data['ip_address'] ?? null;

            if ($attendance->sign_in_at) {
                $attendance->total_minutes = Carbon::parse($attendance->sign_in_at)->diffInMinutes($logTime);
            }

            $attendance->updated_by = $data['user_id'] ?? null;
            $attendance->save();

            $session = HrAttendanceSession::where('business_id', $data['business_id'])
                ->where('employee_id', $data['employee_id'])
                ->where('attendance_date', $date)
                ->where('status', 'open')
                ->orderByDesc('id')
                ->first();

            if ($session) {
                $session->ended_at = $logTime;
                $session->total_minutes = Carbon::parse($session->started_at)->diffInMinutes($logTime);
                $session->status = 'closed';
                $session->save();
            }

            $this->log($attendance, 'sign_out', $logTime, $data);
            $this->updateDailySummary($attendance);

            return $attendance;
        });
    }

    private function log(HrAttendance $attendance, string $type, Carbon $logTime, array $data): void
    {
        if (!DB::getSchemaBuilder()->hasTable('hr_attendance_logs')) {
            return;
        }

        HrAttendanceLog::create([
            'business_id' => $attendance->business_id,
            'employee_id' => $attendance->employee_id,
            'attendance_id' => $attendance->id,
            'log_type' => $type,
            'log_time' => $logTime,
            'source' => $data['source'] ?? 'manual',
            'device_name' => $data['device_name'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'location_text' => $data['location_text'] ?? null,
            'created_by' => $data['user_id'] ?? null,
        ]);
    }

    private function updateDailySummary(HrAttendance $attendance): void
    {
        HrAttendanceDailySummary::updateOrCreate(
            [
                'business_id' => $attendance->business_id,
                'employee_id' => $attendance->employee_id,
                'attendance_date' => $attendance->attendance_date,
            ],
            [
                'first_sign_in_at' => $attendance->sign_in_at,
                'last_sign_out_at' => $attendance->sign_out_at,
                'total_work_minutes' => $attendance->total_minutes ?? 0,
                'late_minutes' => $attendance->late_minutes ?? 0,
                'early_leave_minutes' => $attendance->early_leave_minutes ?? 0,
                'overtime_minutes' => $attendance->overtime_minutes ?? 0,
                'attendance_status' => $attendance->status ?? 'present',
                'approval_status' => $attendance->approval_status ?? 'approved',
                'source_summary' => trim(($attendance->sign_in_source ?? '') . ' / ' . ($attendance->sign_out_source ?? '')),
            ]
        );
    }
}
