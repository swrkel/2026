<?php

namespace Modules\LeadsNew\Services\Release;

class LeadsNewReleaseChecklistService
{
    public function items(): array
    {
        return [
            ['group' => 'Module', 'item' => 'module.json exists', 'status' => 'manual_check'],
            ['group' => 'Module', 'item' => 'Service provider registered', 'status' => 'manual_check'],
            ['group' => 'Routes', 'item' => 'Web routes loaded', 'status' => 'manual_check'],
            ['group' => 'Routes', 'item' => 'API routes loaded where enabled', 'status' => 'manual_check'],
            ['group' => 'Database', 'item' => 'Migrations executed', 'status' => 'manual_check'],
            ['group' => 'Database', 'item' => 'Seeders executed', 'status' => 'manual_check'],
            ['group' => 'Security', 'item' => 'Permissions seeded', 'status' => 'manual_check'],
            ['group' => 'Security', 'item' => 'Sidebar hidden until enabled', 'status' => 'manual_check'],
            ['group' => 'Tenancy', 'item' => 'Tenant data isolation verified', 'status' => 'manual_check'],
            ['group' => 'UI', 'item' => 'Dashboard opens', 'status' => 'manual_check'],
            ['group' => 'UI', 'item' => 'List Leads opens', 'status' => 'manual_check'],
            ['group' => 'UI', 'item' => 'Add/Edit/View pages open', 'status' => 'manual_check'],
            ['group' => 'Reports', 'item' => 'Search/date/export buttons verified', 'status' => 'manual_check'],
            ['group' => 'Logs', 'item' => 'No new Laravel error in storage/logs', 'status' => 'manual_check'],
        ];
    }
}
