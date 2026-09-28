<?php

namespace Modules\Membership\Utils;

use Carbon\Carbon;

class MembershipReportUtil
{
    public function normalizeDateRange(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        return [$start, $end];
    }

    public function money($amount, int $precision = 2): string
    {
        return number_format((float) $amount, $precision, '.', ',');
    }

    public function qty($amount, int $precision = 2): string
    {
        return number_format((float) $amount, $precision, '.', ',');
    }
}
