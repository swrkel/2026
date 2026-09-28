<?php

namespace Modules\MembershipNew\app\Services;

class MembershipNewRedemptionService
{
    public function pointValueToAmount(float $points, float $pointValue): float
    {
        return round(max($points, 0) * max($pointValue, 0), 4);
    }

    public function maxRedeemablePoints(float $availablePoints, float $invoiceAmount, float $pointValue): float
    {
        if ($pointValue <= 0) {
            return 0.0;
        }

        return round(min($availablePoints, $invoiceAmount / $pointValue), 4);
    }
}
