<?php

namespace Modules\SettlementSW\Services;

/** SW_SEP_003 other sales service. */
class SettlementSwOtherSalesService extends SettlementSwBaseService
{
    public function totalSales($items): float
    {
        return $this->sumAmount($items, 'amount');
    }
}
