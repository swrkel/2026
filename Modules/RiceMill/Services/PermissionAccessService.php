<?php
namespace Modules\RiceMill\Services;

use App\Utils\SidebarPermissionUtil;

/**
 * One permission decision path for Rice Mill buttons and route middleware.
 *
 * User Management New is the business-role source for the system. Rice Mill
 * still keeps its historical rice_mill.* permissions for backwards
 * compatibility, but managed roles may authorize through their module rights
 * without requiring Core controller changes.
 */
class PermissionAccessService
{
    public function allows($user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        foreach (['is_super_admin', 'super_admin'] as $field) {
            if (isset($user->{$field}) && (bool) $user->{$field}) {
                return true;
            }
        }

        if (method_exists($user, 'can')) {
            try {
                if ($user->can('superadmin') || $user->can($permission)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Continue to managed-role compatibility; never fail open.
            }
        }

        if (method_exists($user, 'hasRole')) {
            try {
                if ($user->hasRole('Super Admin') || $user->hasRole('superadmin')) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Continue below.
            }
        }

        return $this->managedRoleAllows($permission);
    }

    private function managedRoleAllows(string $permission): bool
    {
        if (! class_exists(SidebarPermissionUtil::class)) {
            return false;
        }

        try {
            if (! SidebarPermissionUtil::usesManagedRoleForCurrentUser()) {
                return false;
            }

            $right = $this->managedRightFor($permission);
            if ($right === null) {
                return false;
            }

            // AutomaticModuleRegistry canonicalizes RiceMill to `ricemill`.
            return SidebarPermissionUtil::managedRoleAllowsPermission(
                'umn.module.ricemill.' . $right
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function managedRightFor(string $permission): ?string
    {
        $suffix = strtolower((string) strrchr($permission, '.'));
        $suffix = ltrim($suffix, '.');

        return match ($suffix) {
            'view' => 'view',
            'create', 'edit', 'update', 'approve', 'complete', 'adjust', 'transfer' => 'edit',
            'delete', 'cancel' => 'delete',
            'print' => 'print',
            'export' => 'pdf',
            default => null,
        };
    }
}
