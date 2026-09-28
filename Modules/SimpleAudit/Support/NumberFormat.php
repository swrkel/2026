<?php

namespace Modules\SimpleAudit\Support;

class NumberFormat
{
    public static function amount($value, $precision)
    {
        return number_format((float) $value, (int) $precision, '.', ',');
    }

    public static function quantity($value, $precision)
    {
        return number_format((float) $value, (int) $precision, '.', ',');
    }
}
