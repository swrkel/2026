<?php

namespace Modules\Tailoring\Services;

class TailoringExecutiveDashboardService
{
    public function summary(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function branchSummary(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function kpis(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
