<?php

namespace Modules\Purchase\Services\Report;

use Modules\Purchase\Utils\Report\PurchaseReportQueryUtil;

class PurchasePaymentReportService
{
    public function data(array $filters): array
    {
        $queryUtil = app(PurchaseReportQueryUtil::class);
        $query = $queryUtil->baseQuery('purchase_payment', $filters);

        return [
            'success' => true,
            'data' => $query->limit(500)->get(),
            'totals' => $queryUtil->totals('purchase_payment', $filters),
        ];
    }
}
