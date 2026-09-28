<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthNursingHandover extends MyHealthBaseModel
{
    protected $table = 'myhealth_nursing_handovers';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'from_nurse_id', 'to_nurse_id',
        'from_shift', 'to_shift', 'patient_summary', 'outstanding_tasks',
        'critical_alerts', 'pending_investigations', 'pending_medications', 'handover_at', 'status'
    ];

    protected $casts = ['handover_at' => 'datetime'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
