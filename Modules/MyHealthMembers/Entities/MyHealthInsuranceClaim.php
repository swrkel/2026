<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthInsuranceClaim extends MyHealthBaseModel
{
    protected $table = 'myhealth_insurance_claims';
    protected $guarded = ['id'];

    public function member()
    {
        return $this->belongsTo(MyHealthMember::class, 'member_id');
    }

    public function policy()
    {
        return $this->belongsTo(MyHealthInsurancePolicy::class, 'policy_id');
    }

    public function items()
    {
        return $this->hasMany(MyHealthInsuranceClaimItem::class, 'claim_id');
    }
}
