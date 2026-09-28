<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * Converts inclusive date filters into timestamp boundaries that can use
 * ordinary indexes on DATETIME/TIMESTAMP columns.
 */
final class IndexFriendlyDateRange
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
