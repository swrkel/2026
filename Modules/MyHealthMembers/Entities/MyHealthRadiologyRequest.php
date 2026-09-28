<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthRadiologyRequest extends Model
{
    protected $table = 'myhealth_radiology_requests';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'consultation_id', 'appointment_id',
        'request_no', 'study_type', 'modality', 'body_part', 'clinical_notes', 'priority',
        'status', 'requested_by', 'scheduled_at', 'performed_at', 'reported_at',
        'verified_at', 'approved_at', 'released_at', 'technician_id', 'radiologist_id',
        'equipment_name', 'room_no', 'remarks', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'performed_at' => 'datetime',
        'reported_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
    ];
}
