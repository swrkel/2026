<?php

namespace Modules\RestaurantNew\Services;

class PermissionService
{
    public function all(): array
    {
        return require __DIR__ . '/../Permissions/permissions.php';
    }
}
