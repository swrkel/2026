<?php

namespace Modules\SettlementSW\Services;

/** SW_SEP_003 credit sale service. */
class SettlementSwCreditSaleService extends SettlementSwBaseService
{
    public function totalCreditSales($items): float
    {
        return $this->sumAmount($items, 'amount');
    }
}
