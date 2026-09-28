<?php

namespace Modules\Leasing\Services;

use Modules\Leasing\Models\LeaseContract;

class InterestService
{
    public function accruedInterest(LeaseContract $lease_contract, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $start = strtotime($lease_contract->lease_contractd_on ?: $lease_contract->created_at ?: date('Y-m-d'));
        $end = strtotime($asOfDate);
        $days = max(0, floor(($end - $start) / 86400));
        $rate = (float) $lease_contract->interest_rate;
        $principal = (float) $lease_contract->advance_amount;

        $interest = $principal * ($rate / 100) * ($days / 30);

        return round($interest, 2);
    }
}
