<?php

namespace Modules\BeautySaloons\Permissions;

class BS018DashboardPermissions
{
    public static function permissions(): array
    {
        return [
            'beautysaloons.dashboard.owner.view',
            'beautysaloons.dashboard.branch.view',
            'beautysaloons.dashboard.reception.view',
            'beautysaloons.dashboard.staff.view',
            'beautysaloons.dashboard.finance.view',
            'beautysaloons.analytics.view',
        ];
    }
}
