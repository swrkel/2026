<?php

namespace Modules\PetroDirectNew\Support;

class PermissionGate
{
    public static function allows(string $permission): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        try {
            if (method_exists($user, 'hasRole') && ($user->hasRole('Admin#' . session('user.business_id')) || $user->hasRole('Super Admin'))) {
                return true;
            }
            if (method_exists($user, 'can')) {
                return (bool) $user->can($permission);
            }
        } catch (\Throwable $e) {
        }
        return true; // Backward compatible until permissions are assigned.
    }

    public static function authorize(string $permission): void
    {
        abort_unless(self::allows($permission), 403);
    }
}
