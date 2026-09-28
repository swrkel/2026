<?php

namespace Modules\EnterpriseFramework\Services\Security;

class EnterprisePermissionService
{
    public function permissions(): array
    {
        return [
            'enterprise_framework.view',
            'enterprise_framework.admin',
            'enterprise_framework.reports.view',
            'enterprise_framework.reports.export',
            'enterprise_framework.reports.print',
            'enterprise_framework.reports.schedule',
            'enterprise_framework.dashboards.view',
            'enterprise_framework.notifications.view',
            'enterprise_framework.audit.view',
        ];
    }

    public function can($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'can')) {
            return (bool) $user->can($permission);
        }

        return false;
    }
}
