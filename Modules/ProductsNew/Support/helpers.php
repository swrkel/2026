<?php

if (! function_exists('productsnew_trans')) {
    /**
     * Translate a Products New language key and never expose a raw key in the UI.
     */
    function productsnew_trans(string $key, ?string $fallback = null, array $replace = [], ?string $locale = null): string
    {
        $fullKey = str_starts_with($key, 'productsnew::') ? $key : 'productsnew::' . $key;
        $translated = __($fullKey, $replace, $locale);

        if (! is_string($translated) || $translated === $fullKey || str_starts_with($translated, 'productsnew::')) {
            return $fallback ?: \Illuminate\Support\Str::headline((string) \Illuminate\Support\Str::afterLast($key, '.'));
        }

        return $translated;
    }
}
