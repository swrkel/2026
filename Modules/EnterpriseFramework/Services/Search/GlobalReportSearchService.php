<?php

namespace Modules\EnterpriseFramework\Services\Search;

use Modules\EnterpriseFramework\Services\Integration\ModuleIntegrationRegistry;

class GlobalReportSearchService
{
    public function __construct(protected ModuleIntegrationRegistry $registry) {}

    public function search(string $term = ''): array
    {
        $term = strtolower(trim($term));
        return array_values(array_filter($this->registry->reports(), function ($report) use ($term) {
            if ($term === '') return true;
            return str_contains(strtolower($report['name'] ?? ''), $term)
                || str_contains(strtolower($report['category'] ?? ''), $term)
                || str_contains(strtolower($report['module_name'] ?? ''), $term);
        }));
    }
}
