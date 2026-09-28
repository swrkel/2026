<?php

namespace Modules\StockTakingNew\Utils;

final class StockTakingPermission
{
    public static function can(string $ability): bool
    {
        return auth()->check() && auth()->user()->can($ability);
    }

    public static function canAny(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (self::can($ability)) {
                return true;
            }
        }
        return false;
    }
}
