<?php

namespace Modules\Poultry\Support;

/**
 * Resolves tenant and user context.
 *
 * The module depends on the SESSION KEYS the ERP already sets, not on core
 * classes. That keeps every file inside Modules/Poultry while still running
 * under the same multi-tenant context as the rest of the application.
 */
class BusinessContext
{
    /** Active business (tenant) id, or null outside an authenticated request. */
    public static function id()
    {
        $businessId = session('business.id');

        if (empty($businessId)) {
            $businessId = session('user.business_id');
        }

        return $businessId ?: null;
    }

    /** Default business location id, when one is selected. */
    public static function locationId()
    {
        return session('business_location_id') ?: session('location_id') ?: null;
    }

    /** Authenticated user id, or null. */
    public static function userId()
    {
        return auth()->check() ? auth()->id() : null;
    }

    /**
     * Guard used by controllers. Throws rather than silently querying with a
     * null business_id, which would otherwise return another tenant's rows on
     * a badly written scope.
     */
    public static function requireId()
    {
        $businessId = static::id();

        if (empty($businessId)) {
            abort(403, 'No active business context.');
        }

        return $businessId;
    }
}
