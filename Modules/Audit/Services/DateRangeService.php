<?php
namespace Modules\Audit\Services;

use Carbon\Carbon;

class DateRangeService
{
    public function resolve(?string $preset, ?string $from = null, ?string $to = null): array
    {
        $today = Carbon::today();
        $preset = $preset ?: config('audit.default_date_preset', 'this_year');

        if ($preset === 'custom' && $from && $to) {
            return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        }

        if ($preset === 'last_year') {
            $d = $today->copy()->subYear();
            return [$d->copy()->startOfYear(), $d->copy()->endOfYear()];
        }

        $fyMonth = (int) config('audit.fiscal_year_start_month', 1);
        if (in_array($preset, ['this_fy', 'last_fy'], true)) {
            $startYear = $today->month >= $fyMonth ? $today->year : $today->year - 1;
            if ($preset === 'last_fy') $startYear--;
            $start = Carbon::create($startYear, $fyMonth, 1)->startOfDay();
            return [$start, $start->copy()->addYear()->subSecond()];
        }

        return [$today->copy()->startOfYear(), $today->copy()->endOfYear()];
    }
}
