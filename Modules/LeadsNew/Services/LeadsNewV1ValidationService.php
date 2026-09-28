<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadsNewV1ValidationService
{
    public function checklist(): array
    {
        return [
            'module_tables' => $this->requiredTables(),
            'tenant_safe_columns' => ['business_id', 'location_id', 'created_by', 'updated_by'],
            'required_workflows' => ['lead', 'followup', 'opportunity', 'document', 'conversion', 'report'],
            'required_exports' => ['excel', 'csv', 'pdf', 'print', 'column_visibility'],
            'required_permissions' => $this->requiredPermissions(),
        ];
    }

    public function run(): array
    {
        $missingTables = [];
        foreach ($this->requiredTables() as $table) {
            if (! Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        return [
            'status' => empty($missingTables) ? 'ready' : 'attention_required',
            'missing_tables' => $missingTables,
            'required_permissions' => $this->requiredPermissions(),
            'message' => empty($missingTables)
                ? 'Leads-New required database tables are available.'
                : 'Some Leads-New tables are missing. Run module migrations before testing.',
        ];
    }

    private function requiredTables(): array
    {
        return [
            'leads_new_leads',
            'leads_new_followups',
            'leads_new_opportunities',
            'leads_new_activities',
            'leads_new_documents',
            'leads_new_settings',
            'leads_new_statuses',
            'leads_new_sources',
            'leads_new_priorities',
            'leads_new_audit_logs',
        ];
    }

    private function requiredPermissions(): array
    {
        return [
            'leads_new.dashboard.view',
            'leads_new.leads.view',
            'leads_new.leads.create',
            'leads_new.leads.update',
            'leads_new.leads.delete',
            'leads_new.followups.view',
            'leads_new.followups.manage',
            'leads_new.opportunities.view',
            'leads_new.opportunities.manage',
            'leads_new.reports.view',
            'leads_new.settings.manage',
        ];
    }
}
