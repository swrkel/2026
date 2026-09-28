<?php

namespace Modules\HRManager\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrAttendance;
use Modules\HRManager\Models\HrAttendanceLog;

class HrAttendanceService
{
    public function signIn(array $data): HrAttendance
    {
        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['log_time'] ?? now())->toDateString();

            $attendance = HrAttendance::firstOrCreate(
                [
                    'business_id' => $data['business_id'],
                    'employee_id' => $data['employee_id'],
                    'attendance_date' => $date,
                ],
                [
                    'shift_id' => $data['shift_id'] ?? null,
                    'status' => 'present',
                    'approval_status' => 'approved',
                    'created_by' => $data['user_id'] ?? null,
                ]
            );

            if (!$attendance->sign_in_at) {
                $attendance->fill([
                    'sign_in_at' => $data['log_time'] ?? now(),
                    'sign_in_source' => $data['source'] ?? 'manual',
                    'sign_in_device' => $data['device_name'] ?? null,
                    'sign_in_ip' => $data['ip_address'] ?? null,
                    'sign_in_location' => $data['location_text'] ?? null,
                    'updated_by' => $data['user_id'] ?? null,
                ])->save();
            }

            $this->log($attendance, 'sign_in', $data);

            return $attendance;
        });
    }

    public function signOut(array $data): HrAttendance
    {
        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['log_time'] ?? now())->toDateString();

            $attendance = HrAttendance::where('business_id', $data['business_id'])
                ->where('employee_id', $data['employee_id'])
                ->where('attendance_date', $date)
                ->firstOrFail();

            $attendance->fill([
                'sign_out_at' => $data['log_time'] ?? now(),
                'sign_out_source' => $data['source'] ?? 'manual',
                'sign_out_device' => $data['device_name'] ?? null,
                'sign_out_ip' => $data['ip_address'] ?? null,
                'sign_out_location' => $data['location_text'] ?? null,
                'updated_by' => $data['user_id'] ?? null,
            ]);

            if ($attendance->sign_in_at && $attendance->sign_out_at) {
                $attendance->total_minutes = Carbon::parse($attendance->sign_in_at)
                    ->diffInMinutes(Carbon::parse($attendance->sign_out_at));
            }

            $attendance->save();

            $this->log($attendance, 'sign_out', $data);

            return $attendance;
        });
    }

    private function log(HrAttendance $attendance, string $type, array $data): void
    {
        HrAttendanceLog::create([
            'business_id' => $attendance->business_id,
            'employee_id' => $attendance->employee_id,
            'attendance_id' => $attendance->id,
            'log_type' => $type,
            'log_time' => $data['log_time'] ?? now(),
            'source' => $data['source'] ?? 'manual',
            'device_name' => $data['device_name'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'location_text' => $data['location_text'] ?? null,
            'confidence_score' => $data['confidence_score'] ?? null,
            'raw_payload' => $data['raw_payload'] ?? null,
            'created_by' => $data['user_id'] ?? null,
        ]);
    }
}
