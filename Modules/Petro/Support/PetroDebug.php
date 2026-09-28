<?php

namespace Modules\Petro\Support;

use Illuminate\Support\Facades\Log;

final class PetroDebug
{
    public static function enabled(): bool
    {
        return (bool) config('petro.debug_logging', false);
    }

    public static function debug($message, $context = []): void
    {
        if (self::enabled()) {
            Log::debug($message, is_array($context) ? $context : []);
        }
    }

    public static function info($message, $context = []): void
    {
        if (self::enabled()) {
            Log::info($message, is_array($context) ? $context : []);
        }
    }
}
