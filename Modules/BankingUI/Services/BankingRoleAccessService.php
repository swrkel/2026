<?php

namespace Modules\BankingUI\Services;

use Modules\BankingUI\Support\BankingMenuMatrix;

class BankingRoleAccessService
{
    public function visibleMenuItems(array $userPermissions): array
    {
        return array_values(array_filter(BankingMenuMatrix::items(), function ($item) use ($userPermissions) {
            return $this->allows($userPermissions, $item['permission']);
        }));
    }

    public function allows(array $permissions, string $needed): bool
    {
        foreach ($permissions as $permission) {
            if ($permission === 'banking.*' || $permission === $needed) {
                return true;
            }
            if (str_ends_with($permission, '.*')) {
                $prefix = substr($permission, 0, -1);
                if (str_starts_with($needed, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
