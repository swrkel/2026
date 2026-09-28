<?php

namespace Modules\Suppliers\Utils\Language;

/**
 * Suppliers module language runtime wrapper.
 */
class SupplierLanguageRuntime
{
    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $key = str_starts_with($key, 'suppliers::') ? $key : 'suppliers::lang.' . $key;

        return __($key, $replace, $locale);
    }

    public static function choice(string $key, int $number, array $replace = [], ?string $locale = null): string
    {
        $key = str_starts_with($key, 'suppliers::') ? $key : 'suppliers::lang.' . $key;

        return trans_choice($key, $number, $replace, $locale);
    }
}
