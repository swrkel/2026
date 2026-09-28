<?php

namespace Modules\Suppliers\Utils;

use Carbon\Carbon;

class SupplierFormatUtil
{
    public static function date($value): string
    {
        return empty($value) ? '' : Carbon::parse($value)->format('Y-m-d');
    }

    public static function datetime($value): string
    {
        return empty($value) ? '' : Carbon::parse($value)->format('Y-m-d H:i');
    }
}
