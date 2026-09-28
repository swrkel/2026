<?php

declare(strict_types=1);

namespace Modules\DailyCollectionSW\Services;

use Carbon\CarbonImmutable;

/**
 * Builds inclusive, index-friendly timestamp ranges for Daily Collection SW.
 *
 * This avoids wrapping database columns in DATE(...), allowing MySQL to use
 * normal indexes on created_at, transaction_date, date and date_and_time.
 */
final class DailyCollectionDateRange
{
    /**
     * @return array{0: string, 1: string}
     */
    public static function inclusive(string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->endOfDay();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ];
    }
}
