<?php

namespace Modules\EnterpriseFramework\Services\Portal;

use Modules\EnterpriseFramework\Services\Integration\ModuleIntegrationRegistry;

class EnterpriseReportingPortalService
{
    public function __construct(protected ModuleIntegrationRegistry $registry) {}

    public function menu(): array
    {
        $grouped = [];
        foreach ($this->registry->reports() as $report) {
            $group = $report['module_name'] ?? 'Other';
            $category = $report['category'] ?? 'General';
            $grouped[$group][$category][] = $report;
        }
        return $grouped;
    }

    public function summary(): array
    {
        $modules = [];
        foreach ($this->registry->all() as $adapter) {
            $modules[] = [
                'key' => $adapter->moduleKey(),
                'name' => $adapter->moduleName(),
                'reports' => count($adapter->reports()),
                'dashboards' => count($adapter->dashboards()),
                'health' => $adapter->health(),
            ];
        }
        return ['modules' => $modules, 'total_reports' => count($this->registry->reports())];
    }
}
