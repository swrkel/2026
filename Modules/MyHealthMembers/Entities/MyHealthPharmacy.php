<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthPharmacy extends MyHealthBaseModel
{
    protected $table = 'myhealth_pharmacies';
    protected $guarded = ['id'];

    public function medicines()
    {
        return $this->hasMany(MyHealthMedicine::class, 'pharmacy_id');
    }
}
