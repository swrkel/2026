<?php

namespace Modules\Distribution\Support\Dependency;

class DistributionDependencyClassifier
{
    public static function isFrameworkAllowed(string $reference): bool
    {
        foreach (['Illuminate\\', 'Carbon\\', 'Yajra\\', 'DB', 'Auth', 'Log', 'Route', 'Schema', 'PDF', 'Excel'] as $allowed) {
            if (str_starts_with(ltrim($reference, '\\'), $allowed)) {
                return true;
            }
        }
        return false;
    }

    public static function isDistributionOwned(string $reference): bool
    {
        return str_starts_with(ltrim($reference, '\\'), 'Modules\\Distribution\\');
    }

    public static function needsSeparation(string $reference): bool
    {
        $ref = ltrim($reference, '\\');
        return str_starts_with($ref, 'App\\') || (str_starts_with($ref, 'Modules\\') && !self::isDistributionOwned($ref));
    }
}
