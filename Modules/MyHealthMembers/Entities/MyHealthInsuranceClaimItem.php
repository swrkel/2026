<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthInsuranceClaimItem extends MyHealthBaseModel
{
    protected $table = 'myhealth_insurance_claim_items';
    protected $guarded = ['id'];

    public function claim()
    {
        return $this->belongsTo(MyHealthInsuranceClaim::class, 'claim_id');
    }
}
