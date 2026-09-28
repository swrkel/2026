<?php

namespace Modules\Chequer\Utils;

use Modules\Chequer\Entities\System;

class FinanceSystemSettings
{
    public static function get(string $key, $default = null)
    {
        try {
            return System::getProperty($key) ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function disabledMessage(): string
    {
        return (string) self::get('not_enalbed_module_user_message', trans('account.no_data_available_in_table'));
    }

    public static function disabledColor(): string
    {
        return (string) self::get('not_enalbed_module_user_color', '#000000');
    }

    public static function disabledFontSize(): string
    {
        return (string) self::get('not_enalbed_module_user_font_size', '14');
    }
}
