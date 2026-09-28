<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrSuccessionCriticalRole;
use Modules\HRManager\Models\HrSuccessionCandidate;
use Modules\HRManager\Models\HrSuccessionAuditLog;

class HrSuccessionPlanningService
{
    public function createCriticalRole(array $data): HrSuccessionCriticalRole
    {
        return DB::transaction(function () use ($data) {
            $role = HrSuccessionCriticalRole::create([
                'business_id'=>$data['business_id'],
                'role_no'=>$data['role_no'] ?? 'SCR-'.now()->format('YmdHis'),
                'role_title'=>$data['role_title'],
                'department_id'=>$data['department_id'] ?? null,
                'designation_id'=>$data['designation_id'] ?? null,
                'current_employee_id'=>$data['current_employee_id'] ?? null,
                'role_level'=>$data['role_level'] ?? 'management',
                'criticality_level'=>$data['criticality_level'] ?? 'high',
                'vacancy_risk'=>$data['vacancy_risk'] ?? 'medium',
                'impact_summary'=>$data['impact_summary'] ?? null,
                'status'=>'active',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['current_employee_id'] ?? null, 'critical_role', $role->id, 'created', null, 'active', 'Critical role created.', $data['user_id'] ?? null);
            return $role;
        });
    }

    public function nominateCandidate(array $data): HrSuccessionCandidate
    {
        return DB::transaction(function () use ($data) {
            $candidate = HrSuccessionCandidate::create([
                'business_id'=>$data['business_id'],
                'candidate_no'=>$data['candidate_no'] ?? 'SUC-CAN-'.now()->format('YmdHis'),
                'critical_role_id'=>$data['critical_role_id'],
                'candidate_pool_id'=>$data['candidate_pool_id'] ?? null,
                'employee_id'=>$data['employee_id'],
                'readiness_level'=>$data['readiness_level'] ?? 'medium_term',
                'readiness_percent'=>$data['readiness_percent'] ?? 0,
                'risk_of_loss'=>$data['risk_of_loss'] ?? 'medium',
                'succession_status'=>'nominated',
                'nominated_by'=>$data['user_id'] ?? null,
                'nominated_at'=>now(),
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'succession_candidate', $candidate->id, 'nominated', null, 'nominated', 'Succession candidate nominated.', $data['user_id'] ?? null);
            return $candidate;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrSuccessionAuditLog::create([
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
