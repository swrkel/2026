<?php

namespace Modules\MyHealthMembers\Http\Controllers\Api;

use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaimItem;
use Modules\MyHealthMembers\Entities\MyHealthInsurancePolicy;

class MyHealthInsuranceApiController extends MyHealthApiBaseController
{
    public function policies()
    {
        return $this->success(['policies' => MyHealthInsurancePolicy::where('member_id', $this->member()->id)->latest('id')->paginate(20)]);
    }

    public function claims()
    {
        return $this->success(['claims' => MyHealthInsuranceClaim::where('member_id', $this->member()->id)->latest('claim_date')->latest('id')->paginate(20)]);
    }

    public function claimItems($claimId)
    {
        $claim = MyHealthInsuranceClaim::where('member_id', $this->member()->id)->findOrFail($claimId);
        $items = MyHealthInsuranceClaimItem::where('claim_id', $claim->id)->get();

        return $this->success(['claim' => $claim, 'items' => $items]);
    }
}
