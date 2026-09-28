<?php

namespace Modules\Membership\Utils;

class MembershipDashboardUtil
{
    public function percentageChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function compactMetric($value): string
    {
        $value = (float) $value;
        if (abs($value) >= 1000000) {
            return number_format($value / 1000000, 2) . 'M';
        }
        if (abs($value) >= 1000) {
            return number_format($value / 1000, 2) . 'K';
        }
        return number_format($value, 0);
    }
}
