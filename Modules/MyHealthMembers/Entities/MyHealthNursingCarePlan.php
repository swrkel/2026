<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthNursingCarePlan extends MyHealthBaseModel
{
    protected $table = 'myhealth_nursing_care_plans';

    protected $fillable = [
        'business_id', 'location_id', 'member_id', 'consultation_id', 'nurse_id',
        'nursing_diagnosis', 'goals', 'interventions', 'outcomes', 'review_date',
        'status', 'remarks'
    ];

    protected $casts = ['review_date' => 'date'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}
