<?php
namespace Modules\HRManager\Services;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrTrainingEnrollment;
use Modules\HRManager\Models\HrTrainingAuditLog;

class HrTrainingService
{
    public function enroll(array $data): HrTrainingEnrollment
    {
        return DB::transaction(function () use ($data) {
            $enrollment = HrTrainingEnrollment::create([
                'business_id'=>$data['business_id'],
                'enrollment_no'=>$data['enrollment_no'] ?? 'TR-ENR-'.now()->format('YmdHis'),
                'training_session_id'=>$data['training_session_id'],
                'training_course_id'=>$data['training_course_id'],
                'employee_id'=>$data['employee_id'],
                'enrollment_status'=>'enrolled',
                'nominated_by'=>$data['user_id'] ?? null,
                'nominated_at'=>now(),
            ]);
            HrTrainingAuditLog::create(['business_id'=>$data['business_id'],'employee_id'=>$data['employee_id'],'reference_type'=>'enrollment','reference_id'=>$enrollment->id,'action'=>'enrolled','new_status'=>'enrolled','note'=>'Employee enrolled for training.','action_by'=>$data['user_id'] ?? null,'action_at'=>now()]);
            return $enrollment;
        });
    }
}
