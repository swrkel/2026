<?php

namespace Modules\LeadsNew\Services\Release;

class LeadsNewStandaloneDependencyReportService
{
    public function expectedOwnedAreas(): array
    {
        return [
            'Controllers', 'Models', 'Services', 'Repositories', 'Requests',
            'Policies', 'Routes', 'Views', 'JavaScript', 'CSS', 'Language files',
            'Migrations', 'Seeders', 'Reports', 'Permissions', 'Config',
        ];
    }

    public function prohibitedBusinessModuleDependencies(): array
    {
        return [
            'Modules\\Leads\\',
            'Modules/Leads/',
            'leads::',
        ];
    }
}
