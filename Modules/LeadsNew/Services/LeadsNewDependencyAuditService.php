<?php

namespace Modules\LeadsNew\Services;

class LeadsNewDependencyAuditService
{
    public function forbiddenReferences(): array
    {
        return [
            'Modules\\Leads\\',
            'leads::',
            '/Modules/Leads/',
            "route('leads.",
            'route("leads.',
        ];
    }

    public function summary(): array
    {
        return [
            'module' => 'LeadsNew',
            'status' => 'standalone',
            'shared_platform_only' => ['auth', 'tenant_db', 'permissions', 'module_registration'],
        ];
    }
}
