<?php

namespace Modules\Pawning\Services;

use Modules\Pawning\Models\Pledge;

class InterestService
{
    public function accruedInterest(Pledge $pledge, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $start = strtotime($pledge->pledged_on ?: $pledge->created_at ?: date('Y-m-d'));
        $end = strtotime($asOfDate);
        $days = max(0, floor(($end - $start) / 86400));
        $rate = (float) $pledge->interest_rate;
        $principal = (float) $pledge->advance_amount;

        $interest = $principal * ($rate / 100) * ($days / 30);

        return round($interest, 2);
    }
}
