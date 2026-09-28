<?php
namespace Modules\HRManager\Services;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrCandidate;
use Modules\HRManager\Models\HrJobApplication;
use Modules\HRManager\Models\HrInterview;

class HrRecruitmentService
{
    public function createCandidate(array $data): HrCandidate
    {
        return HrCandidate::create([
            'business_id'=>$data['business_id'],
            'candidate_no'=>$data['candidate_no'] ?? 'CAN-'.now()->format('YmdHis'),
            'full_name'=>$data['full_name'],
            'mobile'=>$data['mobile'] ?? null,
            'email'=>$data['email'] ?? null,
            'source'=>$data['source'] ?? 'direct',
            'candidate_status'=>'new',
            'created_by'=>$data['user_id'] ?? null,
        ]);
    }

    public function createApplication(array $data): HrJobApplication
    {
        return HrJobApplication::create([
            'business_id'=>$data['business_id'],
            'application_no'=>'APP-'.now()->format('YmdHis'),
            'candidate_id'=>$data['candidate_id'],
            'job_opening_id'=>$data['job_opening_id'],
            'application_date'=>now()->toDateString(),
            'application_status'=>'applied',
        ]);
    }

    public function scheduleInterview(array $data): HrInterview
    {
        return HrInterview::create([
            'business_id'=>$data['business_id'],
            'interview_no'=>'INT-'.now()->format('YmdHis'),
            'application_id'=>$data['application_id'],
            'candidate_id'=>$data['candidate_id'],
            'job_opening_id'=>$data['job_opening_id'],
            'interview_round'=>$data['interview_round'] ?? 'Round 1',
            'interview_type'=>$data['interview_type'] ?? 'in_person',
            'scheduled_at'=>$data['scheduled_at'] ?? null,
            'interviewer_employee_id'=>$data['interviewer_employee_id'] ?? null,
            'interview_status'=>'scheduled',
            'created_by'=>$data['user_id'] ?? null,
        ]);
    }
}
