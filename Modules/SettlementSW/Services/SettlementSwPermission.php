<?php

namespace Modules\SettlementSW\Services;

/**
 * Settlement SW-owned permission resolver.
 *
 * Uses module-specific permission keys first. Legacy ERP keys remain as an
 * optional fallback so existing roles keep working until permissions are seeded
 * fully for Settlement SW.
 */
class SettlementSwPermission
{
    public function canUpdate($user = null): bool
    {
        return $this->canAny(['settlement_sw.update', 'settlement.edit'], $user);
    }

    public function canDelete($user = null): bool
    {
        return $this->canAny(['settlement_sw.delete', 'settlement.delete'], $user);
    }

    public function canAny(array $permissions, $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (empty($user)) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
