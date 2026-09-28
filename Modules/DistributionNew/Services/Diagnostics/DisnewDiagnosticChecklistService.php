<?php

namespace Modules\DistributionNew\Services\Diagnostics;

class DisnewDiagnosticChecklistService
{
    public function serverChecklist(): array
    {
        return [
            'Module folder exists under Modules/DistributionNew',
            'DistributionNew service provider is registered or auto-discovered',
            'Stage SQL / migrations have been applied to the tenant database',
            'Permissions have been seeded for the logged-in business/user role',
            'Sidebar menu is enabled for the business from Super Admin / Manage',
            'Customer bridge and SMS bridge configuration values are available',
            'Queue worker is active for SMS/offline sync jobs where used',
        ];
    }
}
