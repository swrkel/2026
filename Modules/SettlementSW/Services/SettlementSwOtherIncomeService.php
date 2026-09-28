<?php

namespace Modules\SettlementSW\Services;

/** SW_SEP_003 other income service. */
class SettlementSwOtherIncomeService extends SettlementSwBaseService
{
    public function totalIncome($items): float
    {
        return $this->sumAmount($items, 'amount');
    }
}
