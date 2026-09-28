<?php

namespace Modules\BeautySaloons\Utils;

class VoucherNumberUtil
{
    public static function format(string $prefix, int $number, int $padding = 6): string
    {
        return $prefix . str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
    }
}
