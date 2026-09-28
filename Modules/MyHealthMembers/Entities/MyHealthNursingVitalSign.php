<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthNursingVitalSign extends MyHealthBaseModel
{
    protected $table = 'myhealth_nursing_vital_signs';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'appointment_id', 'consultation_id',
        'recorded_by', 'recorded_at', 'temperature', 'pulse', 'respiration',
        'systolic_bp', 'diastolic_bp', 'spo2', 'height_feet', 'height_inches',
        'weight', 'bmi', 'blood_sugar', 'pain_scale', 'status', 'remarks'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:2',
        'weight' => 'decimal:2',
        'bmi' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
