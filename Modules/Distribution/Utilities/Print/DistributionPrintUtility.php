<?php

namespace Modules\Distribution\Utilities\Print;

use Modules\Distribution\Support\DistributionNumberFormatter;

class DistributionPrintUtility
{
    public function money($value, ?int $precision = null): string
    {
        return DistributionNumberFormatter::money($value, $precision);
    }

    public function quantity($value, ?int $precision = null): string
    {
        return DistributionNumberFormatter::qty($value, $precision);
    }

    public function fuelQuantity($value): string
    {
        return DistributionNumberFormatter::fuelQty($value);
    }

    public function date($value): string
    {
        if (empty($value)) {
            return '';
        }

        return date('Y-m-d', strtotime((string) $value));
    }

    public function dateTime($value): string
    {
        if (empty($value)) {
            return '';
        }

        return date('Y-m-d H:i', strtotime((string) $value));
    }
}
