<?php

namespace Modules\Distribution\Utilities\Dates;

class DistributionDateUtility
{
    public function date($value): string
    {
        if (empty($value)) {
            return '';
        }

        return date('Y-m-d', strtotime((string) $value));
    }

    public function dateTime($value): string
    {
        if (empty($value)) {
            return '';
        }

        return date('Y-m-d H:i', strtotime((string) $value));
    }

    public function today(): string
    {
        return date('Y-m-d');
    }
}
