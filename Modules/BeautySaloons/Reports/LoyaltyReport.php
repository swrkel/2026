<?php

namespace Modules\BeautySaloons\Reports;

class LoyaltyReport
{
    public function summary(array $filters = []): array
    {
        return [
            'earned_points' => 0,
            'redeemed_points' => 0,
            'expired_points' => 0,
            'active_balance' => 0,
        ];
    }
}
