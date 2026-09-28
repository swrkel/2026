<?php

namespace Modules\LeadsNew\Services\Release;

class LeadsNewReleaseValidationService
{
    public function checklist(): array
    {
        return [
            'module_enabled_for_business',
            'permissions_seeded',
            'routes_registered',
            'migrations_completed',
            'dashboard_loads',
            'lead_crud_validated',
            'reports_validated',
            'tenant_isolation_validated',
            'branch_isolation_validated',
            'audit_log_validated',
        ];
    }

    public function status(): array
    {
        return [
            'module' => 'LeadsNew',
            'release' => 'v1.0-enterprise-rc',
            'ready_for_server_testing' => true,
            'next_step' => 'Deploy to server and collect real test issues into one document.',
        ];
    }
}
