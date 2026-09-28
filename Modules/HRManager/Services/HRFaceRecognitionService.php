<?php

namespace Modules\HRManager\Services;

use Illuminate\Support\Str;
use Modules\HRManager\Models\HrFaceAttendanceAttempt;
use Modules\HRManager\Models\HrFaceEnrollmentSession;
use Modules\HRManager\Models\HrFaceConsentLog;

class HRFaceRecognitionService
{
    public function startEnrollment(array $data): HrFaceEnrollmentSession
    {
        return HrFaceEnrollmentSession::create([
            'business_id' => $data['business_id'],
            'employee_id' => $data['employee_id'],
            'device_id' => $data['device_id'] ?? null,
            'session_code' => 'FACE-' . strtoupper(Str::random(12)),
            'status' => 'started',
            'started_at' => now(),
            'created_by' => $data['user_id'] ?? null,
        ]);
    }

    public function recordConsent(array $data): HrFaceConsentLog
    {
        return HrFaceConsentLog::create([
            'business_id' => $data['business_id'],
            'employee_id' => $data['employee_id'],
            'consent_status' => $data['consent_status'] ?? 'accepted',
            'consent_text' => $data['consent_text'] ?? 'Employee accepted face attendance consent.',
            'accepted_at' => now(),
            'ip_address' => $data['ip_address'] ?? null,
            'recorded_by' => $data['user_id'] ?? null,
        ]);
    }

    public function verifyAttendanceAttempt(array $payload): HrFaceAttendanceAttempt
    {
        // Provider integration point.
        // At this stage we record the attempt and keep manual fallback safe.
        return HrFaceAttendanceAttempt::create([
            'business_id' => $payload['business_id'],
            'employee_id' => $payload['employee_id'] ?? null,
            'device_id' => $payload['device_id'] ?? null,
            'attempt_type' => $payload['attempt_type'] ?? 'sign_in',
            'attempt_time' => now(),
            'matched' => false,
            'confidence_score' => 0,
            'decision' => 'provider_not_connected',
            'failure_reason' => 'Face recognition provider is not connected yet.',
            'ip_address' => $payload['ip_address'] ?? null,
            'location_text' => $payload['location_text'] ?? null,
            'raw_payload' => $payload,
        ]);
    }
}
