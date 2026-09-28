<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrEmployeeMovement;
use Modules\HRManager\Models\HrEmployeeMovementApproval;
use Modules\HRManager\Models\HrEmployeeLifecycleEvent;
use Modules\HRManager\Models\HrEmployeeMovementAuditLog;

class HrEmployeeMovementService
{
    public function createMovement(array $data): HrEmployeeMovement
    {
        return DB::transaction(function () use ($data) {
            $movement = HrEmployeeMovement::create([
                'business_id'=>$data['business_id'],
                'movement_no'=>$data['movement_no'] ?? 'HR-MOV-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'movement_type'=>$data['movement_type'],
                'effective_date'=>$data['effective_date'] ?? now()->toDateString(),
                'new_department_id'=>$data['new_department_id'] ?? null,
                'new_designation_id'=>$data['new_designation_id'] ?? null,
                'new_location_id'=>$data['new_location_id'] ?? null,
                'new_reporting_manager_id'=>$data['new_reporting_manager_id'] ?? null,
                'new_salary'=>$data['new_salary'] ?? 0,
                'reason'=>$data['reason'] ?? null,
                'movement_status'=>'submitted',
                'approval_status'=>'pending',
                'requested_by'=>$data['user_id'] ?? null,
                'requested_at'=>now(),
            ]);

            HrEmployeeMovementApproval::create([
                'business_id'=>$data['business_id'],
                'employee_movement_id'=>$movement->id,
                'employee_id'=>$data['employee_id'],
                'approval_level'=>1,
                'approval_status'=>'pending',
            ]);

            HrEmployeeLifecycleEvent::create([
                'business_id'=>$data['business_id'],
                'event_no'=>'HR-EVT-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'event_type'=>'movement',
                'event_title'=>'Employee movement submitted',
                'event_date'=>$data['effective_date'] ?? now()->toDateString(),
                'reference_type'=>'employee_movement',
                'reference_id'=>$movement->id,
                'event_status'=>'recorded',
                'created_by'=>$data['user_id'] ?? null,
            ]);

            $this->audit($data['business_id'], $data['employee_id'], 'employee_movement', $movement->id, 'submitted', null, 'pending', 'Employee movement submitted.', $data['user_id'] ?? null);
            return $movement;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrEmployeeMovementAuditLog::create([
            'business_id'=>$businessId,
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
