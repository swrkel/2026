<?php

namespace Modules\Purchase\Services\Report;

use Modules\Purchase\Utils\Report\PurchaseReportQueryUtil;

class PurchaseRegisterReportService
{
    public function data(array $filters): array
    {
        $queryUtil = app(PurchaseReportQueryUtil::class);
        $query = $queryUtil->baseQuery('purchase_register', $filters);

        return [
            'success' => true,
            'data' => $query->limit(500)->get(),
            'totals' => $queryUtil->totals('purchase_register', $filters),
        ];
    }
}
