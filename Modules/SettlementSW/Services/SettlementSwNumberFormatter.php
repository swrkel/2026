<?php

namespace Modules\SettlementSW\Services;

class SettlementSwNumberFormatter
{
    public static function money($value, ?int $precision = null): string
    {
        $precision = $precision ?? (int) config('settlementsw.currency_precision', config('business.currency_precision', 2));
        return number_format((float) $value, $precision, '.', ',');
    }

    public static function quantity($value, ?int $precision = null): string
    {
        $precision = $precision ?? (int) config('settlementsw.quantity_precision', config('business.quantity_precision', 2));
        return number_format((float) $value, $precision, '.', ',');
    }

    public static function meter($value): string
    {
        return number_format((float) $value, 3, '.', ',');
    }
}
