<?php

declare(strict_types=1);

namespace Modules\AutoService\Services;

use Carbon\CarbonImmutable;

/**
 * Converts inclusive business-date filters into index-friendly timestamp
 * boundaries. This avoids DATE(column), which prevents MySQL from using a
 * normal index on the timestamp column.
 */
final class AutoServiceDateRange
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
