<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthDoctorSessionSchedule extends MyHealthBaseModel
{
    protected $table = 'myhealth_doctor_session_schedules';
    protected $guarded = ['id'];

    public function doctor()
    {
        return $this->belongsTo(MyHealthDoctor::class, 'doctor_id');
    }

    public function department()
    {
        return $this->belongsTo(MyHealthHospitalDepartment::class, 'department_id');
    }

    public function room()
    {
        return $this->belongsTo(MyHealthConsultationRoom::class, 'room_id');
    }
}
