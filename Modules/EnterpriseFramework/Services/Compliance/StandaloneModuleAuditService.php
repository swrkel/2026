<?php

namespace Modules\EnterpriseFramework\Services\Compliance;

class StandaloneModuleAuditService
{
    public function checklist(): array
    {
        return [
            'own_routes' => 'Module has exclusive route files.',
            'own_controllers' => 'Module uses its own controllers.',
            'own_services' => 'Module uses its own services/repositories.',
            'own_views' => 'Module uses its own blade views/components.',
            'own_assets' => 'Module uses its own JS/CSS assets.',
            'own_permissions' => 'Module declares its own permissions.',
            'read_only_reports' => 'Reporting pages do not mutate operational data.',
            'branch_consolidated' => 'Reports support branch/location and consolidated modes.',
            'no_controller_coupling' => 'No direct dependency on other module controllers.',
        ];
    }
}
