<?php

namespace Modules\Tailoring\Services;

class TailoringEnterpriseReportService
{
    public function salesReport(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function productionReport(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function fabricReport(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function employeeReport(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
