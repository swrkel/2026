<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthSurgerySchedule extends Model
{
    protected $table = 'myhealth_surgery_schedules';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'consultation_id', 'surgery_no',
        'procedure_name', 'procedure_category', 'priority', 'status', 'theatre_room_id',
        'surgeon_id', 'assistant_surgeon_id', 'anaesthetist_id', 'nurse_in_charge_id',
        'scheduled_start_at', 'scheduled_end_at', 'estimated_duration_minutes',
        'actual_start_at', 'actual_end_at', 'diagnosis', 'clinical_notes',
        'special_instructions', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
    ];
}
