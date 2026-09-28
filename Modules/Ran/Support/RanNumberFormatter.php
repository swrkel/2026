<?php

namespace Modules\Ran\Support;

final class RanNumberFormatter
{
    public static function money($value): string
    {
        return number_format((float) $value, (int) config('ran.currency_decimals', 4), '.', ',');
    }

    public static function weight($value): string
    {
        return number_format((float) $value, (int) config('ran.weight_decimals', 3), '.', ',');
    }

    public static function quantity($value): string
    {
        return number_format((float) $value, (int) config('ran.quantity_decimals', 4), '.', ',');
    }
}
