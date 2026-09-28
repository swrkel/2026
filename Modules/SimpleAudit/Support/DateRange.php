<?php

namespace Modules\SimpleAudit\Support;

use Carbon\Carbon;
use InvalidArgumentException;

class DateRange
{
    public static function normalize($from, $to, $timezone = 'Asia/Colombo')
    {
        try {
            $timezone = $timezone ?: 'Asia/Colombo';
            $now = Carbon::now($timezone);
            $start = Carbon::parse($from ?: $now->copy()->startOfYear()->toDateString(), $timezone)->startOfDay();
            $end = Carbon::parse($to ?: $now->toDateString(), $timezone)->endOfDay();
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(__('simpleaudit::simpleaudit.invalid_date_range'));
        }

        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException(__('simpleaudit::simpleaudit.from_after_to'));
        }

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ];
    }
}
