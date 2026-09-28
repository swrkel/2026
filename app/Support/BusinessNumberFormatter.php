<?php

namespace App\Support;

/** Central tenant-aware currency/quantity formatting rule. */
class BusinessNumberFormatter
{
    public const FUEL_QUANTITY_PRECISION = 3;

    public static function currency($value, bool $withSymbol = false, $business = null): string
    {
        $formatted = self::number($value, self::currencyPrecision($business), $business);
        if (! $withSymbol) return $formatted;

        $currency = session('currency', []);
        $symbol = self::value($business, 'currency_symbol') ?? data_get($currency, 'symbol') ?? data_get(session('business'), 'currency_symbol') ?? '';
        $code = self::value($business, 'currency_code') ?? data_get($currency, 'code') ?? data_get(session('business'), 'currency_code') ?? '';
        if ($code !== '' && ($symbol === '' || trim((string) $symbol) === '$') && strtoupper((string) $code) !== 'USD') $symbol = $code;
        $placement = self::value($business, 'currency_symbol_placement') ?? data_get(session('business'), 'currency_symbol_placement') ?? data_get($currency, 'currency_symbol_placement') ?? 'before';
        if (trim((string) $symbol) === '') return $formatted;

        return $placement === 'after' ? trim($formatted . ' ' . $symbol) : trim($symbol . ' ' . $formatted);
    }

    public static function quantity($value, $productOrCategory = null, $business = null): string
    {
        return self::number($value, self::isFuel($productOrCategory) ? 3 : self::quantityPrecision($business), $business);
    }

    public static function fuelQuantity($value, $business = null): string
    {
        return self::number($value, 3, $business);
    }

    public static function number($value, int $precision, $business = null): string
    {
        $currency = session('currency', []);
        $decimal = (string) (self::value($business, 'decimal_separator') ?? data_get($currency, 'decimal_separator') ?? data_get(session('business'), 'decimal_separator') ?? '.');
        $thousand = (string) (self::value($business, 'thousand_separator') ?? data_get($currency, 'thousand_separator') ?? data_get(session('business'), 'thousand_separator') ?? ',');
        return number_format(self::normalize($value, $decimal, $thousand), min(10, max(0, $precision)), $decimal, $thousand);
    }

    public static function currencyPrecision($business = null): int
    {
        return self::precision(self::value($business, 'currency_precision') ?? session('business.currency_precision') ?? data_get(session('business'), 'currency_precision') ?? config('constants.currency_precision', 2), 2);
    }

    public static function quantityPrecision($business = null): int
    {
        return self::precision(self::value($business, 'quantity_precision') ?? session('business.quantity_precision') ?? data_get(session('business'), 'quantity_precision') ?? config('constants.quantity_precision', 2), 2);
    }

    public static function isFuel($source): bool
    {
        if (is_string($source)) return strtolower(trim($source)) === 'fuel';
        if (! is_array($source) && ! is_object($source)) return false;
        foreach (['category_name', 'category.name', 'category.category_name', 'product.category.name', 'product.category.category_name'] as $key) {
            if (strtolower(trim((string) data_get($source, $key, ''))) === 'fuel') return true;
        }
        return ! data_get($source, 'category') && ! data_get($source, 'product') && strtolower(trim((string) data_get($source, 'name', ''))) === 'fuel';
    }

    private static function precision($value, int $fallback): int
    {
        return $value === null || $value === '' || ! is_numeric($value) ? $fallback : min(10, max(0, (int) $value));
    }

    private static function value($business, string $key)
    {
        return $business === null ? null : data_get($business, $key);
    }

    private static function normalize($value, string $decimal, string $thousand): float
    {
        if ($value === null || $value === '') return 0.0;
        if (is_int($value) || is_float($value)) return (float) $value;
        $normalized = trim((string) $value);
        if ($thousand !== '') $normalized = str_replace($thousand, '', $normalized);
        if ($decimal !== '.' && $decimal !== '') $normalized = str_replace($decimal, '.', $normalized);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);
        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
