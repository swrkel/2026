<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthDoctorSchedule extends MyHealthBaseModel
{
    protected $table = 'myhealth_doctor_schedules';
    protected $guarded = ['id'];

    public function doctor()
    {
        return $this->belongsTo(MyHealthDoctor::class, 'doctor_id');
    }
}
