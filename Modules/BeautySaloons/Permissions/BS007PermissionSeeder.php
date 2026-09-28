<?php

namespace Modules\BeautySaloons\Permissions;

class BS007PermissionSeeder
{
    public static function permissions(): array
    {
        return [
            'beauty_saloons.memberships.view',
            'beauty_saloons.memberships.create',
            'beauty_saloons.memberships.update',
            'beauty_saloons.packages.view',
            'beauty_saloons.packages.create',
            'beauty_saloons.packages.update',
            'beauty_saloons.packages.consume',
            'beauty_saloons.membership_reports.view',
        ];
    }
}
