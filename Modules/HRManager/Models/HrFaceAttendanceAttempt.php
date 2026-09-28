<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrFaceAttendanceAttempt extends Model
{
    protected $table = 'hr_face_attendance_attempts';

    protected $fillable = [
        'business_id', 'employee_id', 'device_id', 'attempt_type', 'attempt_time',
        'matched', 'confidence_score', 'decision', 'failure_reason',
        'ip_address', 'location_text', 'raw_payload'
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}
