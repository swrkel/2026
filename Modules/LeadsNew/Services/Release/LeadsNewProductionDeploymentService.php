<?php

namespace Modules\LeadsNew\Services\Release;

class LeadsNewProductionDeploymentService
{
    public function checklist(): array
    {
        return [
            'module_folder_present' => 'Modules/LeadsNew folder exists',
            'routes_registered' => 'Leads-New web routes are registered',
            'permissions_seeded' => 'Leads-New permissions are seeded and assigned',
            'migrations_completed' => 'Leads-New migrations completed on tenant DB',
            'sidebar_enabled' => 'Sidebar appears only for enabled businesses and permitted users',
            'existing_leads_untouched' => 'Existing Leads module files are not modified',
        ];
    }

    public function smokeRoutes(): array
    {
        return [
            'leads-new.index' => '/leads-new',
            'leads-new.dashboard' => '/leads-new/dashboard',
            'leads-new.leads.index' => '/leads-new/leads',
            'leads-new.settings.index' => '/leads-new/settings',
            'leads-new.reports.index' => '/leads-new/reports',
        ];
    }
}
