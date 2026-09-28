<?php

namespace Modules\PetroGeneral\Utils;

use Illuminate\Support\Facades\Auth;

class PetroGeneralTenantGuard
{
    public static function businessId(): ?int
    {
        $sessionBusinessId = session('business.id') ?: session('user.business_id');

        if (!empty($sessionBusinessId)) {
            return (int) $sessionBusinessId;
        }

        if (Auth::check() && !empty(Auth::user()->business_id)) {
            return (int) Auth::user()->business_id;
        }

        return null;
    }

    public static function locationId(): ?int
    {
        $locationId = session('business_location_id') ?: session('user.business_location_id');

        return !empty($locationId) ? (int) $locationId : null;
    }

    public static function ensureBusinessId(): int
    {
        $businessId = self::businessId();

        if (empty($businessId)) {
            abort(403, 'Petro General tenant context not found.');
        }

        return $businessId;
    }
}
