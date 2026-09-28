<?php

namespace Modules\Suppliers\Utils;

use Carbon\Carbon;

class SupplierDateRangeUtil
{
    public static function normalize(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        return [$start, $end];
    }
}
