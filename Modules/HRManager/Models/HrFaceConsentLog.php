<?php

namespace Modules\HRManager\Models;

use Illuminate\Database\Eloquent\Model;

class HrFaceConsentLog extends Model
{
    protected $table = 'hr_face_consent_logs';

    protected $fillable = [
        'business_id', 'employee_id', 'consent_type', 'consent_status',
        'consent_text', 'accepted_at', 'revoked_at', 'ip_address', 'recorded_by'
    ];
}
