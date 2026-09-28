<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMedicalHistory extends MyHealthBaseModel
{
    protected $table = 'myhealth_medical_histories';
    protected $guarded = ['id'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }
}

