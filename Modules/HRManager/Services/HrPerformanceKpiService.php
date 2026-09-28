<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrEmployeeKpiAssignment;
use Modules\HRManager\Models\HrPerformanceAppraisal;
use Modules\HRManager\Models\HrPerformanceKpiAuditLog;

class HrPerformanceKpiService
{
    public function assignKpis(array $data): HrEmployeeKpiAssignment
    {
        return DB::transaction(function () use ($data) {
            $assignment = HrEmployeeKpiAssignment::create([
                'business_id'=>$data['business_id'],
                'assignment_no'=>$data['assignment_no'] ?? 'KPI-ASG-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'performance_cycle_id'=>$data['performance_cycle_id'],
                'assignment_status'=>'active',
                'assigned_by'=>$data['user_id'] ?? null,
                'assigned_at'=>now(),
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'kpi_assignment', $assignment->id, 'assigned', null, 'active', 'KPI assignment created.', $data['user_id'] ?? null);
            return $assignment;
        });
    }

    public function createAppraisal(array $data): HrPerformanceAppraisal
    {
        return DB::transaction(function () use ($data) {
            $appraisal = HrPerformanceAppraisal::create([
                'business_id'=>$data['business_id'],
                'appraisal_no'=>$data['appraisal_no'] ?? 'APR-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'performance_cycle_id'=>$data['performance_cycle_id'],
                'appraisal_type'=>$data['appraisal_type'] ?? 'annual',
                'appraisal_status'=>'draft',
                'approval_status'=>'pending',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'appraisal', $appraisal->id, 'created', null, 'draft', 'Performance appraisal created.', $data['user_id'] ?? null);
            return $appraisal;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrPerformanceKpiAuditLog::create([
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
