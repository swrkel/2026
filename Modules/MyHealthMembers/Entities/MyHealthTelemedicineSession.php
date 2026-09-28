<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthTelemedicineSession extends MyHealthBaseModel
{
    protected $table = 'myhealth_telemedicine_sessions';
    protected $guarded = ['id'];

    public function appointment()
    {
        return $this->belongsTo(MyHealthTelemedicineAppointment::class, 'appointment_id');
    }
}
