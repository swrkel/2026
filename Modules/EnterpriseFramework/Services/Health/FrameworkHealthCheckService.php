<?php

namespace Modules\EnterpriseFramework\Services\Health;

use Modules\EnterpriseFramework\Services\Integration\ModuleIntegrationRegistry;

class FrameworkHealthCheckService
{
    public function __construct(protected ModuleIntegrationRegistry $registry) {}

    public function run(): array
    {
        $modules = [];
        foreach ($this->registry->all() as $adapter) {
            $modules[] = $adapter->health();
        }
        return [
            'framework' => 'Enterprise Framework',
            'status' => 'ready',
            'registered_modules' => count($modules),
            'modules' => $modules,
        ];
    }
}
