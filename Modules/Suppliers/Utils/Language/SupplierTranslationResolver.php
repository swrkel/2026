<?php

namespace Modules\Suppliers\Utils\Language;

class SupplierTranslationResolver
{
    public static function label(string $key, array $replace = []): string
    {
        return SupplierLanguageRuntime::trans($key, $replace);
    }

    public static function labels(array $keys): array
    {
        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = self::label($key);
        }
        return $labels;
    }
}
