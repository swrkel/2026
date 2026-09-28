<?php

namespace Modules\HRManager\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrLeaveRequest;
use Modules\HRManager\Models\HrLeaveRequestDay;
use Modules\HRManager\Models\HrLeaveApprovalLog;

class HrLeaveService
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
                'request_no' => $data['request_no'] ?? ('LV-' . now()->format('YmdHis')),
                'from_date' => $from->toDateString(),
                'to_date' => $to->toDateString(),
                'total_days' => $data['total_days'] ?? $totalDays,
                'half_day_type' => $data['half_day_type'] ?? null,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
                'requested_by' => $data['user_id'] ?? null,
            ]);

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                HrLeaveRequestDay::create([
                    'business_id' => $data['business_id'],
                    'leave_request_id' => $request->id,
                    'employee_id' => $data['employee_id'],
                    'leave_date' => $date->toDateString(),
                    'day_value' => 1.00,
                    'day_type' => 'full_day',
                    'status' => 'pending',
                ]);
            }

            $this->log($request, 'created', null, 'pending', 'Leave request created.', $data['user_id'] ?? null);

            return $request;
        });
    }

    public function approve(HrLeaveRequest $request, ?int $userId = null): HrLeaveRequest
    {
        return DB::transaction(function () use ($request, $userId) {
            $fromStatus = $request->status;
            $request->fill([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ])->save();

            HrLeaveRequestDay::where('leave_request_id', $request->id)->update(['status' => 'approved']);
            $this->log($request, 'approved', $fromStatus, 'approved', 'Leave approved.', $userId);

            return $request;
        });
    }

    public function reject(HrLeaveRequest $request, string $reason, ?int $userId = null): HrLeaveRequest
    {
        return DB::transaction(function () use ($request, $reason, $userId) {
            $fromStatus = $request->status;
            $request->fill([
                'status' => 'rejected',
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejected_reason' => $reason,
            ])->save();

            HrLeaveRequestDay::where('leave_request_id', $request->id)->update(['status' => 'rejected']);
            $this->log($request, 'rejected', $fromStatus, 'rejected', $reason, $userId);

            return $request;
        });
    }

    private function log(HrLeaveRequest $request, string $action, ?string $from, string $to, ?string $note, ?int $userId): void
    {
        HrLeaveApprovalLog::create([
            'business_id' => $request->business_id,
            'leave_request_id' => $request->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'action_by' => $userId,
            'action_at' => now(),
        ]);
    }
}
