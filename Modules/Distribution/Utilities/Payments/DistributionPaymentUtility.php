<?php

namespace Modules\Distribution\Utilities\Payments;

use Modules\Distribution\Support\DistributionNumberFormatter;

class DistributionPaymentUtility
{
    public function amount($value, ?int $precision = null): string
    {
        return DistributionNumberFormatter::money($value, $precision);
    }

    public function normalizeAmount($value): float
    {
        return DistributionNumberFormatter::raw($value);
    }

    public function balance($total, $paid): float
    {
        return round($this->normalizeAmount($total) - $this->normalizeAmount($paid), 6);
    }

    public function isFullyPaid($total, $paid): bool
    {
        return $this->balance($total, $paid) <= 0.000001;
    }
}
