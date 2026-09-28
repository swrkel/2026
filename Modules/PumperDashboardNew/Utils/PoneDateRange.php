<?php

namespace Modules\PumperDashboardNew\Utils;

use Carbon\CarbonImmutable;

final class PoneDateRange
{
    /** @return array{0:?CarbonImmutable,1:?CarbonImmutable} */
    public static function fromFilters(array $filters): array
    {
        return [
            self::parse($filters['from'] ?? null, false),
            self::parse($filters['to'] ?? null, true),
        ];
    }

    private static function parse(mixed $value, bool $endOfDay): ?CarbonImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
