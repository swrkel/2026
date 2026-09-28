<?php

namespace Modules\UserManagementNew\Services;

use App\User;

/**
 * Shared user passcode lookup.
 *
 * The database column is historically named `pump_operator_passcode` because
 * the passcode was first introduced for Pump Operators. It is now the single
 * passcode for dashboard / quick-access authentication across modules.
 *
 * Do not create module-specific passcode columns or tables. Modules should use
 * this service and continue to enforce their own permissions after the user is
 * identified.
 */
class UserPasscodeService
{
    public function findActiveUserByPasscode(int $businessId, string $passcode): ?User
    {
        $passcode = trim($passcode);
        if ($businessId <= 0 || $passcode === '') {
            return null;
        }

        $user = User::query()
            ->where('business_id', $businessId)
            ->where('pump_operator_passcode', $passcode)
            ->first();

        if (! $user) {
            return null;
        }

        if (isset($user->status) && strtolower(trim((string) $user->status)) !== 'active') {
            return null;
        }

        return $user;
    }
}
