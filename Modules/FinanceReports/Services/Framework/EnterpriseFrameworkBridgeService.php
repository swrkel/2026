<?php

namespace Modules\FinanceReports\Services\Framework;

use Modules\EnterpriseFramework\Services\Registry\ReportRegistryService;
use Modules\FinanceReports\Services\Adapters\FinanceReportsEnterpriseAdapter;

class EnterpriseFrameworkBridgeService
{
    public function __construct(
        protected FinanceReportsEnterpriseAdapter $adapter,
        protected ?ReportRegistryService $registry = null
    ) {}

    public function registerReports(): array
    {
        $reports = $this->adapter->reports();

        if ($this->registry) {
            foreach ($reports as $report) {
                $this->registry->register(array_merge([
                    'module' => $this->adapter->moduleName(),
                    'read_only' => true,
                    'supports_branch' => true,
                    'supports_consolidated' => true,
                    'supports_export' => true,
                    'supports_print' => true,
                ], $report));
            }
        }

        return [
            'registered' => count($reports),
            'module' => $this->adapter->moduleName(),
            'read_only' => true,
        ];
    }

    public function status(): array
    {
        return [
            'adapter' => $this->adapter->health(),
            'metrics' => $this->adapter->metrics(),
            'registry_available' => $this->registry !== null,
        ];
    }
}
