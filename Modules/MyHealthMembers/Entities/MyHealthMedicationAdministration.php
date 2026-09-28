<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMedicationAdministration extends MyHealthBaseModel
{
    protected $table = 'myhealth_medication_administrations';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'prescription_id', 'medicine_id',
        'nurse_id', 'medicine_name', 'dose', 'route', 'time_due', 'time_given',
        'status', 'missed_reason', 'adverse_reaction', 'remarks'
    ];

    protected $casts = [
        'time_due' => 'datetime',
        'time_given' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
