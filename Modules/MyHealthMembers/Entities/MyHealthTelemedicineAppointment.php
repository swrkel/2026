<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthTelemedicineAppointment extends MyHealthBaseModel
{
    protected $table = 'myhealth_telemedicine_appointments';
    protected $guarded = ['id'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }

    public function doctor()
    {
        return $this->belongsTo(MyHealthDoctor::class, 'doctor_id');
    }

    public function schedule()
    {
        return $this->belongsTo(MyHealthDoctorSchedule::class, 'schedule_id');
    }

    public function session()
    {
        return $this->hasOne(MyHealthTelemedicineSession::class, 'appointment_id');
    }
}
