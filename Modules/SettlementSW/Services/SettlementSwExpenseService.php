<?php

namespace Modules\SettlementSW\Services;

/** SW_SEP_003 expense service. */
class SettlementSwExpenseService extends SettlementSwBaseService
{
    public function totalExpenses($expenses): float
    {
        return $this->sumAmount($expenses, 'amount');
    }
}
