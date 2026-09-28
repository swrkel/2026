<?php

namespace Modules\SettlementSW\Services;

use Carbon\Carbon;

class SettlementSwDateFormatter
{
    public static function date($value): string
    {
        return empty($value) ? '' : Carbon::parse($value)->format('Y-m-d');
    }

    public static function dateTime($value): string
    {
        return empty($value) ? '' : Carbon::parse($value)->format('Y-m-d H:i');
    }
}
