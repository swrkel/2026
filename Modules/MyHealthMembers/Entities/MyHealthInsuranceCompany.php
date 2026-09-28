<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthInsuranceCompany extends MyHealthBaseModel
{
    protected $table = 'myhealth_insurance_companies';
    protected $guarded = ['id'];

    public function policies()
    {
        return $this->hasMany(MyHealthInsurancePolicy::class, 'insurance_company_id');
    }
}
