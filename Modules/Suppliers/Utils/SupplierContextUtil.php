<?php

namespace Modules\Suppliers\Utils;

/**
 * Suppliers module-local runtime context wrapper.
 *
 * All Suppliers controllers/services must use this wrapper instead of calling
 * host application helpers directly in many files. The only unavoidable host
 * boundary is resolved here: current session/authenticated user/business.
 */
class SupplierContextUtil
{
    public static function businessId(): int
    {
        $businessId = self::sessionValue('user.business_id')
            ?: data_get(self::user(), 'business_id')
            ?: self::sessionValue('business.id');

        return (int) $businessId;
    }

    public static function locationId(): ?int
    {
        $locationId = self::sessionValue('business_location.id')
            ?: self::sessionValue('location_id')
            ?: self::sessionValue('user.location_id')
            ?: data_get(self::user(), 'location_id');

        return $locationId !== null ? (int) $locationId : null;
    }

    public static function userId(): ?int
    {
        $id = data_get(self::user(), 'id');
        return $id !== null ? (int) $id : null;
    }

    public static function user()
    {
        return function_exists('auth') ? auth()->user() : null;
    }

    public static function check(): bool
    {
        return self::user() !== null && self::businessId() > 0;
    }

    public static function can(string $permission): bool
    {
        $user = self::user();

        if (! $user) {
            return false;
        }

        // UserManagementNew managed roles are authoritative in this ERP.
        // Use the strict role-aware resolver when available so Suppliers obeys
        // the same intersection/role-boundary rules as the global middleware.
        if (method_exists($user, 'roleAllowsPermission')) {
            return (bool) $user->roleAllowsPermission($permission, self::businessId());
        }

        if (method_exists($user, 'can')) {
            return (bool) $user->can($permission);
        }

        return false;
    }

    public static function requirePermission(string $permission): void
    {
        abort_unless(self::can($permission), 403, 'Unauthorized action.');
    }

    public static function requireSupplierAccess($supplier): void
    {
        abort_unless(
            $supplier
            && (int) data_get($supplier, 'business_id') === self::businessId()
            && in_array(data_get($supplier, 'type'), ['supplier', 'both'], true),
            404
        );
    }

    public static function sessionValue(string $key, $default = null)
    {
        return function_exists('session') ? session($key, $default) : $default;
    }
}
