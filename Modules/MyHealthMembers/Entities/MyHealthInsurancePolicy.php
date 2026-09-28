<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthInsurancePolicy extends MyHealthBaseModel
{
    protected $table = 'myhealth_insurance_policies';
    protected $guarded = ['id'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }

    public function company()
    {
        return $this->belongsTo(MyHealthInsuranceCompany::class, 'insurance_company_id');
    }

    public function claims()
    {
        return $this->hasMany(MyHealthInsuranceClaim::class, 'policy_id');
    }
}
