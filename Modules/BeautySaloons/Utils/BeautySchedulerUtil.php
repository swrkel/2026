<?php

namespace Modules\BeautySaloons\Utils;

use Carbon\Carbon;

class BeautySchedulerUtil
{
    public static function minutesBetween(?string $start, ?string $end): int
    {
        if (!$start || !$end) {
            return 0;
        }
        return max(0, Carbon::parse($start)->diffInMinutes(Carbon::parse($end), false));
    }

    public static function appointmentNumber(): string
    {
        return 'BSA-' . now()->format('YmdHis');
    }
}
