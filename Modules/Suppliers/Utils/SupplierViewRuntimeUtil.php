<?php

namespace Modules\Suppliers\Utils;

/**
 * Suppliers module-local view runtime wrapper.
 *
 * Blade files must use this class instead of spreading framework helper calls
 * through many supplier views. This keeps the Suppliers UI self-contained and
 * gives one controlled boundary for locale, flash, route and query lookups.
 */
class SupplierViewRuntimeUtil
{
    public static function locale(): string
    {
        return function_exists('app') ? (string) app()->getLocale() : 'en';
    }

    /**
     * Normalize both the module's string flash messages and the host
     * application's standard ['success' => ..., 'msg' => ...] payloads.
     */
    public static function statusPayload(): ?array
    {
        $status = SupplierContextUtil::sessionValue('status');

        if ($status === null || $status === '') {
            return null;
        }

        $success = true;
        $message = $status;

        if (is_array($status)) {
            $success = (bool) ($status['success'] ?? true);
            $message = $status['msg'] ?? $status['message'] ?? null;
        }

        if (is_array($message)) {
            $parts = [];
            array_walk_recursive($message, static function ($value) use (&$parts): void {
                if (is_scalar($value) && trim((string) $value) !== '') {
                    $parts[] = trim((string) $value);
                }
            });
            $message = implode(' ', $parts);
        } elseif (is_object($message) && method_exists($message, '__toString')) {
            $message = (string) $message;
        } elseif (! is_scalar($message)) {
            $message = null;
        }

        $message = trim((string) ($message ?? ''));

        if ($message === '') {
            return null;
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    public static function statusMessage(): ?string
    {
        $payload = self::statusPayload();

        return $payload['message'] ?? null;
    }

    public static function routeParam(string $key, $default = null)
    {
        if (! function_exists('request')) {
            return $default;
        }

        return request()->route($key) ?? $default;
    }

    public static function input(string $key, $default = null)
    {
        return function_exists('request') ? request()->input($key, $default) : $default;
    }

    /**
     * Resolve a supplier route key from an Eloquent model, route value,
     * explicit view value, or the supplier_id query string.
     */
    public static function supplierId($candidate = null): ?int
    {
        if ($candidate === null) {
            $candidate = self::routeParam('supplier')
                ?? self::routeParam('supplier_id')
                ?? self::input('supplier_id');
        }

        if (is_object($candidate)) {
            if (method_exists($candidate, 'getRouteKey')) {
                $candidate = $candidate->getRouteKey();
            } else {
                $candidate = data_get($candidate, 'id');
            }
        }

        if (! is_numeric($candidate) || (int) $candidate <= 0) {
            return null;
        }

        return (int) $candidate;
    }

    public static function routeIs(string $route): bool
    {
        return function_exists('request') && request()->routeIs($route);
    }

    public static function query(): array
    {
        return function_exists('request') ? request()->query() : [];
    }
}
