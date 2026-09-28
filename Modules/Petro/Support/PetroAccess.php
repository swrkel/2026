<?php

namespace Modules\Petro\Support;

use App\Utils\SidebarPermissionUtil;

/**
 * Single access rule for the legacy Petro module.
 *
 * Manage Side Bar is the business-level parent. Business administrators,
 * legacy Petro roles and roles created by User Management New are then
 * evaluated consistently by both the sidebar and route middleware.
 */
final class PetroAccess
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

        if ($user->can('superadmin') || $user->can('petro.access')) {
            return true;
        }

        try {
            if ($user->hasRole('Admin#'.$businessId)) {
                return true;
            }
        } catch (\Throwable $exception) {
            // Continue to the managed-role permission below.
        }

        return $user->can('umn.module.petro.view');
    }

    public static function isVisibleInSidebar(?int $businessId = null): bool
    {
        $businessId = self::businessId($businessId);

        return $businessId > 0
            && SidebarPermissionUtil::isVisibleInSidebar('petro', $businessId)
            && self::userCanAccess(auth()->user(), $businessId);
    }

    public static function isEnabledForRequest(?int $businessId = null): bool
    {
        $businessId = self::businessId($businessId);

        return $businessId > 0
            && SidebarPermissionUtil::isEnabled('petro', $businessId)
            && self::userCanAccess(auth()->user(), $businessId);
    }
}
