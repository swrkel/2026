<?php

namespace Modules\StockTakingNew\Reports;

use Modules\StockTakingNew\Services\ReportService;

class AccuracyReport
{
    public function __construct(private ReportService $reports) {}

    public function query(int $businessId, array $filters = [])
    {
        return $this->reports->sessionQuery($businessId, $filters)
            ->whereIn('status', ['submitted', 'approved', 'posted']);
    }
}
