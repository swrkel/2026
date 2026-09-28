<?php

namespace Modules\LeadsNew\Services;

class LeadsNewProductionReadinessService
{
    public function checklist(): array
    {
        return [
            'module_registration' => 'Verify module.json and provider registration.',
            'tenant_isolation' => 'Verify each query is scoped by business/location where applicable.',
            'permissions' => 'Verify view/create/edit/delete/report/settings permissions.',
            'sidebar' => 'Verify hidden unless business flag and role permission are enabled.',
            'crud' => 'Verify Add/Edit/View/Delete/Archive/Restore/Duplicate.',
            'reports' => 'Verify search/date/export/print/column visibility.',
            'assets' => 'Verify LeadsNew JS/CSS load independently.',
            'audit' => 'Verify important actions write activity/audit history.',
        ];
    }
}
