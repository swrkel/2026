<?php

namespace Modules\PetroDirect\Support;

use Illuminate\Support\Facades\Log;

final class PetroDirectDebug
{
    public static function enabled(): bool
    {
        return (bool) config('petrodirect.debug_logging', false);
    }

    public static function debug($message, array $context = []): void
    {
        if (self::enabled()) {
            Log::debug($message, $context);
        }
    }

    public static function info($message, array $context = []): void
    {
        if (self::enabled()) {
            Log::info($message, $context);
        }
    }
}
