<?php

namespace Modules\StockTakingNew\Reports;

use Modules\StockTakingNew\Services\ReportService;

class AuditTrailReport
{
    public function __construct(private ReportService $reports) {}

    public function query(int $businessId, array $filters = [])
    {
        return $this->reports->auditQuery($businessId, $filters);
    }
}
