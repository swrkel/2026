<?php

namespace Modules\Purchase\Services\Report;

use Modules\Purchase\Utils\Report\PurchaseReportQueryUtil;

class ProductPurchaseReportService
{
    public function data(array $filters): array
    {
        $queryUtil = app(PurchaseReportQueryUtil::class);
        $query = $queryUtil->baseQuery('product_purchase', $filters);

        return [
            'success' => true,
            'data' => $query->limit(500)->get(),
            'totals' => $queryUtil->totals('product_purchase', $filters),
        ];
    }
}
