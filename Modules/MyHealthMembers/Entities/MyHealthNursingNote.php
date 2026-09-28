<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthNursingNote extends MyHealthBaseModel
{
    protected $table = 'myhealth_nursing_notes';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'appointment_id', 'consultation_id',
        'nurse_id', 'shift', 'note_type', 'observations', 'doctor_instructions',
        'nursing_actions', 'medication_notes', 'escalation_notes', 'status', 'noted_at'
    ];

    protected $casts = ['noted_at' => 'datetime'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
