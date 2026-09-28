<?php

namespace Modules\Distribution\Utilities\Formatting;

use Modules\Distribution\Support\DistributionNumberFormatter;

class DistributionCurrencyFormatter
{
    public function format($value, ?int $precision = null): string
    {
        return DistributionNumberFormatter::money($value, $precision);
    }

    public function parse($value): float
    {
        return DistributionNumberFormatter::raw($value);
    }
}
