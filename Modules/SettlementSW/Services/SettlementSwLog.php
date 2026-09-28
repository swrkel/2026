<?php

namespace Modules\SettlementSW\Services;

use Illuminate\Support\Facades\Log;

/**
 * Debug/info logging gate for Settlement SW.
 *
 * Error, warning and emergency logs remain unchanged in controllers. Verbose
 * request and calculation logs are disabled by default to prevent disk I/O and
 * oversized logs on busy settlement pages. They can be enabled temporarily with
 * SETTLEMENTSW_DEBUG_LOGGING=true.
 */
class SettlementSwLog
{
    public static function enabled(): bool
    {
        return (bool) config('settlementsw.debug_logging', false);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (self::enabled()) {
            Log::debug($message, $context);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        if (self::enabled()) {
            Log::info($message, $context);
        }
    }
}
