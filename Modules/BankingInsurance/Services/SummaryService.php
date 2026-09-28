<?php

namespace Modules\BankingInsurance\Services;

use Modules\BankingInsurance\Entities\Policy;
use Modules\BankingInsurance\Entities\Premium;
use Modules\BankingInsurance\Entities\Claim;

class SummaryService
{
    public function dashboard($business_id, $location_id = null)
    {
        $policy = Policy::forBusiness($business_id);
        $premium = Premium::where('business_id', $business_id);
        $claim = Claim::where('business_id', $business_id);

        if (!empty($location_id)) {
            $policy->where('location_id', $location_id);
            $premium->where('location_id', $location_id);
            $claim->where('location_id', $location_id);
        }

        return [
            'total_policies' => (clone $policy)->count(),
            'active_policies' => (clone $policy)->where('status', 'active')->count(),
            'premium_collected' => (float) (clone $premium)->sum('amount'),
            'pending_claims' => (clone $claim)->whereIn('status', ['submitted', 'under_review', 'approved'])->count(),
            'claim_amount' => (float) (clone $claim)->sum('claim_amount'),
            'settled_claim_amount' => (float) (clone $claim)->where('status', 'settled')->sum('approved_amount'),
        ];
    }
}
