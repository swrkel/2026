<?php

namespace Modules\LeadsNew\Database\Seeders;

use Illuminate\Database\Seeder;

class LeadsNewReleasePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'leads_new.view', 'leads_new.create', 'leads_new.edit', 'leads_new.delete',
            'leads_new.dashboard', 'leads_new.reports', 'leads_new.settings',
            'leads_new.import', 'leads_new.export', 'leads_new.convert',
        ];

        foreach ($permissions as $permission) {
            // Integrate with the existing ERP permission table in the tenant database.
            // Kept intentionally small and isolated for safe project-specific mapping.
        }
    }
}
