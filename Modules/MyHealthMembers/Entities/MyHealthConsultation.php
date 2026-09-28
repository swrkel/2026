<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthConsultation extends MyHealthBaseModel
{
    protected $table = 'myhealth_consultations';
    protected $guarded = ['id'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }

    public function doctor()
    {
        return $this->belongsTo(MyHealthDoctor::class, 'doctor_id');
    }
}
