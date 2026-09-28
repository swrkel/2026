<?php

namespace Modules\LeadsNew\Services\Validation;

class LeadsNewServerSmokeValidator
{
    public function expectedRoutes(): array
    {
        return [
            'leads-new.index',
            'leads-new.dashboard',
            'leads-new.leads.index',
            'leads-new.leads.create',
            'leads-new.settings.index',
            'leads-new.reports.index',
            'leads-new.release.checklist',
        ];
    }

    public function expectedPermissions(): array
    {
        return [
            'leads_new.view',
            'leads_new.create',
            'leads_new.edit',
            'leads_new.delete',
            'leads_new.reports',
            'leads_new.settings',
            'leads_new.import',
            'leads_new.export',
        ];
    }

    public function expectedTables(): array
    {
        return [
            'leads_new_leads',
            'leads_new_followups',
            'leads_new_notes',
            'leads_new_documents',
            'leads_new_opportunities',
            'leads_new_settings',
        ];
    }
}
