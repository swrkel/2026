<?php

namespace Modules\EnterpriseFramework\Services\Integration;

use Modules\EnterpriseFramework\Contracts\ModuleReportAdapterContract;

class ModuleIntegrationRegistry
{
    protected array $adapters = [];

    public function register(ModuleReportAdapterContract $adapter): void
    {
        $this->adapters[$adapter->moduleKey()] = $adapter;
    }

    public function all(): array
    {
        return $this->adapters;
    }

    public function get(string $moduleKey): ?ModuleReportAdapterContract
    {
        return $this->adapters[$moduleKey] ?? null;
    }

    public function reports(): array
    {
        $reports = [];
        foreach ($this->adapters as $adapter) {
            foreach ($adapter->reports() as $report) {
                $report['module_key'] = $adapter->moduleKey();
                $report['module_name'] = $adapter->moduleName();
                $reports[] = $report;
            }
        }
        return $reports;
    }
}
