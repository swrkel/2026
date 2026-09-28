<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthConsultationRoom extends MyHealthBaseModel
{
    protected $table = 'myhealth_consultation_rooms';
    protected $guarded = ['id'];

    public function department()
    {
        return $this->belongsTo(MyHealthHospitalDepartment::class, 'department_id');
    }
}
