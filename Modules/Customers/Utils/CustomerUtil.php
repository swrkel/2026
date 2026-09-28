<?php

namespace Modules\Customers\Utils;

class CustomerUtil
{
    public static function normalizeAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', $value);
    }
}
