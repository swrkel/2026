<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrRecruitmentCandidate;
use Modules\HRManager\Models\HrRecruitmentApplication;
use Modules\HRManager\Models\HrRecruitmentAuditLog;

class HrRecruitmentTalentService
{
    public function createCandidate(array $data): HrRecruitmentCandidate
    {
        return DB::transaction(function () use ($data) {
            $candidate = HrRecruitmentCandidate::create([
                'business_id'=>$data['business_id'],
                'candidate_no'=>$data['candidate_no'] ?? 'CAN-'.now()->format('YmdHis'),
                'full_name'=>$data['full_name'],
                'mobile'=>$data['mobile'] ?? null,
                'email'=>$data['email'] ?? null,
                'nic_no'=>$data['nic_no'] ?? null,
                'source'=>$data['source'] ?? 'direct',
                'candidate_status'=>'new',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $candidate->id, 'candidate', $candidate->id, 'created', null, 'new', 'Candidate created.', $data['user_id'] ?? null);
            return $candidate;
        });
    }

    public function apply(array $data): HrRecruitmentApplication
    {
        return DB::transaction(function () use ($data) {
            $application = HrRecruitmentApplication::create([
                'business_id'=>$data['business_id'],
                'application_no'=>$data['application_no'] ?? 'APP-'.now()->format('YmdHis'),
                'candidate_id'=>$data['candidate_id'],
                'vacancy_id'=>$data['vacancy_id'],
                'application_date'=>$data['application_date'] ?? now()->toDateString(),
                'current_stage'=>'applied',
                'application_status'=>'active',
            ]);
            $this->audit($data['business_id'], $data['candidate_id'], 'application', $application->id, 'applied', null, 'active', 'Candidate application created.', $data['user_id'] ?? null);
            return $application;
        });
    }

    private function audit($businessId, $candidateId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrRecruitmentAuditLog::create([
            'business_id'=>$businessId,
            'candidate_id'=>$candidateId,
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
