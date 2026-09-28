<?php

namespace Modules\PumperDashboardNew\Utils;

final class PoneDecimal
{
    public static function money(mixed $value): float
    {
        return round((float) $value, 4);
    }

    public static function quantity(mixed $value): float
    {
        return round((float) $value, 6);
    }

    public static function unitPrice(mixed $value): float
    {
        return round((float) $value, 6);
    }

    public static function lineTotal(mixed $quantity, mixed $unitPrice, mixed $discount = 0): float
    {
        return self::money(
            self::quantity($quantity) * self::unitPrice($unitPrice) - self::money($discount)
        );
    }
}
