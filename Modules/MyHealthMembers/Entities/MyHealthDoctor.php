<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthDoctor extends MyHealthBaseModel
{
    protected $table = 'myhealth_doctors';
    protected $guarded = ['id'];

    public function schedules()
    {
        return $this->hasMany(MyHealthDoctorSchedule::class, 'doctor_id');
    }
}

