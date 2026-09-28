<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthDispense extends MyHealthBaseModel
{
    protected $table = 'myhealth_dispenses';
    protected $guarded = ['id'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }

    public function prescription()
    {
        return $this->belongsTo(MyHealthPrescription::class, 'prescription_id');
    }

    public function items()
    {
        return $this->hasMany(MyHealthDispenseItem::class, 'dispense_id');
    }
}
