<?php

namespace Modules\PetroGeneral\Support;

use App\Utils\SidebarPermissionUtil;

/**
 * One business and role access rule for the standalone Petro General module.
 */
final class PetroGeneralAccess
{
    public static function businessId(?int $businessId = null): int
    {
        return (int) (
            $businessId
            ?: session('user.business_id')
            ?: session('business.id')
            ?: optional(auth()->user())->business_id
        );
    }

    public static function userCanAccess($user = null, ?int $businessId = null): bool
    {
        $user = $user ?: auth()->user();
        $businessId = self::businessId($businessId);

        if (! $user || $businessId <= 0) {
            return false;
        }

        if (
            $user->can('superadmin')
            || $user->can('petrogeneral.access')
            || $user->can('petro_general.access')
        ) {
            return true;
        }

        try {
            if ($user->hasRole('Admin#'.$businessId)) {
                return true;
            }
        } catch (\Throwable $exception) {
            // Continue to the User Management New permission.
        }

        return $user->can('umn.module.petro_general.view');
    }

    public static function isVisibleInSidebar(?int $businessId = null): bool
    {
        $businessId = self::businessId($businessId);

        return $businessId > 0
            && SidebarPermissionUtil::isVisibleInSidebar('petro_general', $businessId)
            && self::userCanAccess(auth()->user(), $businessId);
    }
}
