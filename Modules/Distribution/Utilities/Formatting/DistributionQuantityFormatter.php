<?php

namespace Modules\Distribution\Utilities\Formatting;

use Modules\Distribution\Support\DistributionNumberFormatter;

class DistributionQuantityFormatter
{
    public function format($value, ?int $precision = null): string
    {
        return DistributionNumberFormatter::qty($value, $precision);
    }

    public function fuel($value): string
    {
        return DistributionNumberFormatter::fuelQty($value);
    }

    public function parse($value): float
    {
        return DistributionNumberFormatter::raw($value);
    }
}
