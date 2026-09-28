<?php

namespace Modules\BeautySaloons\Utils;

class BeautyNumberUtil
{
    public static function money($amount, int $precision = 2): string
    {
        return number_format((float) $amount, $precision, '.', ',');
    }
}
