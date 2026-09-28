<?php

namespace Modules\HRManager\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrLeaveRequest;
use Modules\HRManager\Models\HrLeaveRequestApproval;
use Modules\HRManager\Models\HrLeaveCalendarDay;

class HrLeaveCentralService
{
    public function createRequest(array $data): HrLeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $from = Carbon::parse($data['from_date']);
            $to = Carbon::parse($data['to_date']);
            $totalDays = $from->diffInDays($to) + 1;

            $request = HrLeaveRequest::create([
                'business_id' => $data['business_id'],
                'employee_id' => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'request_no' => $data['request_no'] ?? 'LV-' . now()->format('YmdHis'),
                'from_date' => $from->toDateString(),
                'to_date' => $to->toDateString(),
                'total_days' => $data['total_days'] ?? $totalDays,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
                'requested_by' => $data['user_id'] ?? null,
            ]);

            HrLeaveRequestApproval::create([
                'business_id' => $data['business_id'],
                'leave_request_id' => $request->id,
                'employee_id' => $data['employee_id'],
                'level_no' => 1,
                'approval_status' => 'pending',
            ]);

            return $request;
        });
    }

    public function approve(HrLeaveRequest $request, ?int $userId = null): HrLeaveRequest
    {
        return DB::transaction(function () use ($request, $userId) {
            $request->status = 'approved';
            $request->approved_by = $userId;
            $request->approved_at = now();
            $request->save();

            HrLeaveRequestApproval::where('leave_request_id', $request->id)
                ->where('approval_status', 'pending')
                ->update(['approval_status' => 'approved', 'approver_user_id' => $userId, 'approved_at' => now()]);

            $from = Carbon::parse($request->from_date);
            $to = Carbon::parse($request->to_date);

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                HrLeaveCalendarDay::updateOrCreate(
                    [
                        'business_id' => $request->business_id,
                        'employee_id' => $request->employee_id,
                        'calendar_date' => $date->toDateString(),
                        'leave_request_id' => $request->id,
                    ],
                    [
                        'leave_type_id' => $request->leave_type_id,
                        'day_value' => 1.00,
                        'calendar_status' => 'approved_leave',
                        'display_title' => 'Approved Leave #' . $request->request_no,
                    ]
                );
            }

            return $request;
        });
    }

    public function reject(HrLeaveRequest $request, string $reason, ?int $userId = null): HrLeaveRequest
    {
        $request->status = 'rejected';
        $request->rejected_by = $userId;
        $request->rejected_at = now();
        $request->rejected_reason = $reason;
        $request->save();

        HrLeaveRequestApproval::where('leave_request_id', $request->id)
            ->where('approval_status', 'pending')
            ->update(['approval_status' => 'rejected', 'approver_user_id' => $userId, 'approval_note' => $reason, 'rejected_at' => now()]);

        return $request;
    }
}
