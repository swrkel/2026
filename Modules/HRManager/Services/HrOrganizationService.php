<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrOrganizationUnit;
use Modules\HRManager\Models\HrOrganizationPosition;
use Modules\HRManager\Models\HrEmployeePositionAssignment;
use Modules\HRManager\Models\HrOrganizationAuditLog;

class HrOrganizationService
{
    public function createUnit(array $data): HrOrganizationUnit
    {
        return DB::transaction(function () use ($data) {
            $unit = HrOrganizationUnit::create([
                'business_id'=>$data['business_id'],
                'unit_no'=>$data['unit_no'] ?? 'ORG-U-'.now()->format('YmdHis'),
                'unit_name'=>$data['unit_name'],
                'unit_type'=>$data['unit_type'] ?? 'department',
                'parent_unit_id'=>$data['parent_unit_id'] ?? null,
                'manager_employee_id'=>$data['manager_employee_id'] ?? null,
                'unit_status'=>'active',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['manager_employee_id'] ?? null, 'organization_unit', $unit->id, 'created', null, 'active', 'Organization unit created.', $data['user_id'] ?? null);
            return $unit;
        });
    }

    public function createPosition(array $data): HrOrganizationPosition
    {
        return DB::transaction(function () use ($data) {
            $position = HrOrganizationPosition::create([
                'business_id'=>$data['business_id'],
                'position_no'=>$data['position_no'] ?? 'ORG-P-'.now()->format('YmdHis'),
                'position_title'=>$data['position_title'],
                'organization_unit_id'=>$data['organization_unit_id'] ?? null,
                'reports_to_position_id'=>$data['reports_to_position_id'] ?? null,
                'position_level'=>$data['position_level'] ?? 'staff',
                'approved_headcount'=>$data['approved_headcount'] ?? 1,
                'position_status'=>'active',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], null, 'organization_position', $position->id, 'created', null, 'active', 'Organization position created.', $data['user_id'] ?? null);
            return $position;
        });
    }

    public function assignEmployee(array $data): HrEmployeePositionAssignment
    {
        return DB::transaction(function () use ($data) {
            $assignment = HrEmployeePositionAssignment::create([
                'business_id'=>$data['business_id'],
                'assignment_no'=>$data['assignment_no'] ?? 'ORG-A-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'organization_unit_id'=>$data['organization_unit_id'] ?? null,
                'position_id'=>$data['position_id'],
                'reports_to_employee_id'=>$data['reports_to_employee_id'] ?? null,
                'effective_from'=>$data['effective_from'] ?? now()->toDateString(),
                'assignment_type'=>$data['assignment_type'] ?? 'primary',
                'assignment_status'=>'active',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'position_assignment', $assignment->id, 'assigned', null, 'active', 'Employee assigned to organization position.', $data['user_id'] ?? null);
            return $assignment;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrOrganizationAuditLog::create([
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
