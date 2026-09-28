<?php

namespace Modules\Distribution\Support;

/**
 * Distribution-owned number formatting helper.
 *
 * Kept independent from App\Utils so Distribution screens, reports, prints and
 * exports can share the same formatting rules without calling main ERP helpers.
 */
class DistributionNumberFormatter
{
    public static function money($value, ?int $precision = null): string
    {
        return self::format($value, $precision ?? 2);
    }

    public static function currency($value, ?int $precision = null): string
    {
        return self::money($value, $precision);
    }

    public static function qty($value, ?int $precision = null): string
    {
        return self::format($value, $precision ?? 2);
    }

    public static function fuelQty($value): string
    {
        return self::format($value, 3);
    }

    public static function price($value, ?int $precision = null): string
    {
        return self::money($value, $precision);
    }

    public static function percent($value, int $precision = 2): string
    {
        return self::format($value, $precision);
    }

    public static function plain($value, int $precision = 2): string
    {
        return self::format($value, $precision);
    }

    public static function raw($value): float
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    public static function format($value, int $precision = 2): string
    {
        return number_format(self::raw($value), $precision, '.', ',');
    }
}
