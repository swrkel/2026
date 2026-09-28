<?php

namespace Modules\Membership\Utils;

class MembershipPointsCalculator
{
    public function calculateEarnedPoints(float $amount, float $pointsPerAmount = 1.0, float $amountStep = 100.0): float
    {
        if ($amount <= 0 || $amountStep <= 0) {
            return 0.0;
        }

        return floor($amount / $amountStep) * $pointsPerAmount;
    }

    public function calculateRedeemAmount(float $points, float $valuePerPoint = 1.0): float
    {
        if ($points <= 0 || $valuePerPoint <= 0) {
            return 0.0;
        }

        return $points * $valuePerPoint;
    }

    public function closingBalance(float $opening, float $earned, float $redeemed, float $adjustment = 0.0): float
    {
        return $opening + $earned - $redeemed + $adjustment;
    }
}
