<?php

namespace Modules\LeadsNew\Reports;

use Modules\LeadsNew\Services\LeadsNewExecutiveDashboardService;

class LeadsNewExecutiveSummaryReport
{
    public function __construct(protected LeadsNewExecutiveDashboardService $service) {}

    public function build(array $filters = []): array
    {
        return [
            'title' => 'Leads-New Executive Summary',
            'filters' => $filters,
            'summary' => $this->service->summary($filters),
            'funnel' => $this->service->funnel($filters),
        ];
    }
}
