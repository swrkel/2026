<?php

namespace Modules\StockTakingNew\Utils;

final class StockTakingFormatter
{
    public static function quantity($value, ?int $decimals = null): string
    {
        return number_format((float) $value, $decimals ?? (int) config('stocktakingnew.defaults.quantity_decimals', 4));
    }

    public static function amount($value, ?int $decimals = null): string
    {
        return number_format((float) $value, $decimals ?? (int) config('stocktakingnew.defaults.amount_decimals', 4));
    }

    public static function fileName(string $value, string $extension): string
    {
        $base = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $value), '-');
        return ($base !== '' ? $base : 'stock-taking-document') . '.' . ltrim($extension, '.');
    }
}
