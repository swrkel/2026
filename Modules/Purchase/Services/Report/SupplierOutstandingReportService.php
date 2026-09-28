<?php

namespace Modules\Purchase\Services\Report;

use Modules\Purchase\Utils\Report\PurchaseReportQueryUtil;

class SupplierOutstandingReportService
{
    public function data(array $filters): array
    {
        $queryUtil = app(PurchaseReportQueryUtil::class);
        $query = $queryUtil->baseQuery('supplier_outstanding', $filters);

        return [
            'success' => true,
            'data' => $query->limit(500)->get(),
            'totals' => $queryUtil->totals('supplier_outstanding', $filters),
        ];
    }
}
