<?php

namespace Modules\BankingTradeFinance\Services;

class TradeFinanceDashboardService
{
    public function summary(): array
    {
        return [
            'active_lc' => 0,
            'active_guarantees' => 0,
            'pending_documents' => 0,
            'maturing_this_month' => 0,
        ];
    }
}
