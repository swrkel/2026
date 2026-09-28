<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthClaimSettlement extends MyHealthBaseModel
{
    protected $table = 'myhealth_claim_settlements';
    protected $guarded = ['id'];

    public function claim() { return $this->belongsTo(MyHealthInsuranceClaim::class, 'claim_id'); }
}
