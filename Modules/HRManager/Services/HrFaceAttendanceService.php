<?php
namespace Modules\HRManager\Services;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrFaceProfile;
use Modules\HRManager\Models\HrFaceAttendanceLog;
use Modules\HRManager\Models\HrFaceVerificationLog;

class HrFaceAttendanceService
{
    public function enroll(array $data): HrFaceProfile
    {
        return DB::transaction(function () use ($data) {
            return HrFaceProfile::updateOrCreate(
                ['business_id'=>$data['business_id'],'employee_id'=>$data['employee_id']],
                ['profile_code'=>$data['profile_code'] ?? 'FACE-'.$data['employee_id'],'quality_score'=>$data['quality_score'] ?? 0,'enrollment_source'=>$data['enrollment_source'] ?? 'web_camera','enrollment_status'=>'enrolled','active'=>1,'enrolled_by'=>$data['user_id'] ?? null,'enrolled_at'=>now(),'remarks'=>$data['remarks'] ?? null]
            );
        });
    }

    public function recordAttendance(array $data): HrFaceAttendanceLog
    {
        return DB::transaction(function () use ($data) {
            $log = HrFaceAttendanceLog::create([
                'business_id'=>$data['business_id'],'location_id'=>$data['location_id'] ?? null,'employee_id'=>$data['employee_id'] ?? null,'face_profile_id'=>$data['face_profile_id'] ?? null,'device_id'=>$data['device_id'] ?? null,
                'log_no'=>'FACE-LOG-'.now()->format('YmdHis'),'log_type'=>$data['log_type'] ?? 'sign_in','log_time'=>$data['log_time'] ?? now(),'recognition_status'=>$data['recognition_status'] ?? 'success',
                'confidence_score'=>$data['confidence_score'] ?? 0,'recognition_time_ms'=>$data['recognition_time_ms'] ?? 0,'captured_image_path'=>$data['captured_image_path'] ?? null,'source'=>$data['source'] ?? 'face_device','ip_address'=>$data['ip_address'] ?? null,'decision_note'=>$data['decision_note'] ?? null,
            ]);

            HrFaceVerificationLog::create(['business_id'=>$data['business_id'],'employee_id'=>$data['employee_id'] ?? null,'device_id'=>$data['device_id'] ?? null,'verification_type'=>'attendance','verification_status'=>$log->recognition_status,'confidence_score'=>$log->confidence_score,'verified_at'=>$log->log_time]);

            return $log;
        });
    }
}
