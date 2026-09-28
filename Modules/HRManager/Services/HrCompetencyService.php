<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrCompetency;
use Modules\HRManager\Models\HrEmployeeCompetencyAssessment;
use Modules\HRManager\Models\HrCompetencyAuditLog;

class HrCompetencyService
{
    public function createCompetency(array $data): HrCompetency
    {
        return DB::transaction(function () use ($data) {
            $competency = HrCompetency::create([
                'business_id'=>$data['business_id'],
                'competency_code'=>$data['competency_code'] ?? 'COMP-'.now()->format('YmdHis'),
                'competency_name'=>$data['competency_name'],
                'competency_group_id'=>$data['competency_group_id'] ?? null,
                'competency_type'=>$data['competency_type'] ?? 'technical',
                'description'=>$data['description'] ?? null,
                'status'=>1,
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], null, 'competency', $competency->id, 'created', null, 'active', 'Competency created.', $data['user_id'] ?? null);
            return $competency;
        });
    }

    public function createAssessment(array $data): HrEmployeeCompetencyAssessment
    {
        return DB::transaction(function () use ($data) {
            $assessment = HrEmployeeCompetencyAssessment::create([
                'business_id'=>$data['business_id'],
                'assessment_no'=>$data['assessment_no'] ?? 'COMP-ASSESS-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'assessment_date'=>$data['assessment_date'] ?? now()->toDateString(),
                'assessment_type'=>$data['assessment_type'] ?? 'annual',
                'assessment_status'=>'draft',
                'approval_status'=>'pending',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'competency_assessment', $assessment->id, 'created', null, 'draft', 'Employee competency assessment created.', $data['user_id'] ?? null);
            return $assessment;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrCompetencyAuditLog::create([
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
