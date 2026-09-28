<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrMssTeamMember;
use Modules\HRManager\Models\HrMssApprovalQueue;
use Modules\HRManager\Models\HrMssAuditLog;

class HrMssService
{
    public function addTeamMember(array $data): HrMssTeamMember
    {
        return DB::transaction(function () use ($data) {
            $member = HrMssTeamMember::updateOrCreate(
                [
                    'business_id'=>$data['business_id'],
                    'manager_employee_id'=>$data['manager_employee_id'],
                    'employee_id'=>$data['employee_id'],
                ],
                [
                    'reporting_type'=>$data['reporting_type'] ?? 'direct',
                    'effective_from'=>$data['effective_from'] ?? now()->toDateString(),
                    'status'=>1,
                    'created_by'=>$data['user_id'] ?? null,
                ]
            );

            $this->audit($data['business_id'], $data['manager_employee_id'], $data['employee_id'], 'team_member', $member->id, 'added', null, 'active', 'Team member assigned to manager.', $data['user_id'] ?? null);
            return $member;
        });
    }

    public function createApproval(array $data): HrMssApprovalQueue
    {
        return DB::transaction(function () use ($data) {
            $approval = HrMssApprovalQueue::create([
                'business_id'=>$data['business_id'],
                'approval_no'=>$data['approval_no'] ?? 'MSS-APR-'.now()->format('YmdHis'),
                'manager_employee_id'=>$data['manager_employee_id'],
                'employee_id'=>$data['employee_id'] ?? null,
                'reference_type'=>$data['reference_type'],
                'reference_id'=>$data['reference_id'],
                'approval_title'=>$data['approval_title'],
                'approval_amount'=>$data['approval_amount'] ?? 0,
                'approval_status'=>'pending',
                'priority'=>$data['priority'] ?? 'normal',
                'submitted_at'=>now(),
            ]);

            $this->audit($data['business_id'], $data['manager_employee_id'], $data['employee_id'] ?? null, 'approval', $approval->id, 'created', null, 'pending', 'Manager approval created.', $data['user_id'] ?? null);
            return $approval;
        });
    }

    private function audit($businessId, $managerEmployeeId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrMssAuditLog::create([
            'business_id'=>$businessId,
            'manager_employee_id'=>$managerEmployeeId,
            'employee_id'=>$employeeId,
            'reference_type'=>$type,
            'reference_id'=>$id,
            'action'=>$action,
            'old_status'=>$old,
            'new_status'=>$new,
            'note'=>$note,
            'action_by'=>$userId,
            'action_at'=>now(),
        ]);
    }
}
