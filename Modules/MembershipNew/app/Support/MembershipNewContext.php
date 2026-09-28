<?php

namespace Modules\MembershipNew\app\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MembershipNewContext
{
    /**
     * Resolve the active business without touching the users table unless the
     * normal business session keys are genuinely unavailable. This keeps
     * Membership New fast on tenant and central-database businesses alike.
     */
    public static function businessIdOrNull(): ?int
    {
        foreach (['business.id', 'user.business_id', 'business_id'] as $key) {
            $candidate = session($key);

            if ($candidate !== null && $candidate !== '' && is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        // Only resolve the authenticated user as a final fallback. Building an
        // eager candidates array here can cause an unnecessary user query on
        // every request even when business.id is already in the session.
        $user = Auth::user();
        $candidate = $user ? ($user->business_id ?? null) : null;

        if ($candidate !== null && $candidate !== '' && is_numeric($candidate) && (int) $candidate > 0) {
            return (int) $candidate;
        }

        return null;
    }

    public static function businessId(): int
    {
        $businessId = self::businessIdOrNull();

        abort_if($businessId === null, 403, 'No active business is selected for Membership New.');

        return $businessId;
    }

    /**
     * Follow the application's active/default connection. The host tenancy layer
     * may point this to a tenant database, while a central-database business can
     * legitimately continue on the central database.
     */
    public static function connectionName(): string
    {
        $name = DB::getDefaultConnection();

        return is_string($name) && $name !== ''
            ? $name
            : (string) config('database.default', 'mysql');
    }


    /**
     * System-standard rows-per-page selector used by every Membership New list.
     */
    public static function perPage(int $default = 25): int
    {
        $allowed = [10, 25, 50, 100, 200];
        $value = (int) request()->input('per_page', $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function databaseName(): ?string
    {
        try {
            $name = DB::connection(self::connectionName())->getDatabaseName();
            return is_string($name) && $name !== '' ? $name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
