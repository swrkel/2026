<?php

namespace Modules\RiceMill\Services;

use App\User;
use Modules\UserManagementNew\Services\UserPasscodeService;

/**
 * @deprecated Rice Mill no longer owns a separate dashboard passcode store.
 *             Kept only as a compatibility shim for previously deployed code.
 */
class DashboardUserAccessService
{
    public function __construct(private UserPasscodeService $passcodes)
    {
    }

    public function tableReady(): bool
    {
        return true;
    }

    public function accessForUser(int $businessId, int $userId): ?object
    {
        return null;
    }

    public function syncUserAccess(
        int $businessId,
        int $userId,
        bool $enabled,
        ?string $plainPasscode = null,
        ?int $actorId = null
    ): void {
        // Intentionally no-op. The shared user passcode is managed in the users table.
    }

    public function findUserByPasscode(int $businessId, string $passcode): ?User
    {
        return $this->passcodes->findActiveUserByPasscode($businessId, $passcode);
    }

    public function markLogin(int $businessId, int $userId, ?string $ip): void
    {
        // Login auditing, if required, should be implemented as a shared facility.
    }
}
