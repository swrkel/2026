<?php

namespace Modules\MembershipNew\app\Permissions;

class MembershipNewPermissions
{
    public static function all(): array
    {
        return MembershipNewPermissionRegistry::flat();
    }
}
