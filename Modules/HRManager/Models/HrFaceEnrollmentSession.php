<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrFaceEnrollmentSession extends Model
{
    protected $table = 'hr_face_enrollment_sessions';

    protected $fillable = [
        'business_id', 'employee_id', 'device_id', 'session_code', 'status',
        'capture_count', 'quality_score', 'started_at', 'completed_at',
        'cancelled_at', 'created_by'
    ];
}
