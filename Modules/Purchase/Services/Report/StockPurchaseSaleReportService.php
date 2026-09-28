<?php

namespace Modules\Purchase\Services\Report;

use Modules\Purchase\Utils\Report\PurchaseReportQueryUtil;

class StockPurchaseSaleReportService
{
    public function data(array $filters): array
    {
        $queryUtil = app(PurchaseReportQueryUtil::class);
        $query = $queryUtil->baseQuery('stock_purchase_sale', $filters);

        return [
            'success' => true,
            'data' => $query->limit(500)->get(),
            'totals' => $queryUtil->totals('stock_purchase_sale', $filters),
        ];
    }
}
