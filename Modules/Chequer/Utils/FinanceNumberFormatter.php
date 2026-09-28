<?php

namespace Modules\Chequer\Utils;

class FinanceNumberFormatter
{
    public static function money($amount, int $precision = 2): string
    {
        return number_format((float) $amount, $precision, '.', ',');
    }

    public static function quantity($amount, int $precision = 2): string
    {
        return number_format((float) $amount, $precision, '.', ',');
    }

    public static function date($value): string
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d', strtotime($value));
    }

    public static function dateTime($value): string
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d H:i', strtotime($value));
    }
}
