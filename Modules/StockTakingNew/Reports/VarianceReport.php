<?php

namespace Modules\StockTakingNew\Reports;

use Modules\StockTakingNew\Services\ReportService;

class VarianceReport
{
    public function __construct(private ReportService $reports) {}

    public function query(int $businessId, array $filters = [])
    {
        return $this->reports->varianceQuery($businessId, $filters);
    }
}
