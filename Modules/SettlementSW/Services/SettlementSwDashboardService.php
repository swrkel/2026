<?php

namespace Modules\SettlementSW\Services;

/**
 * SW_SEP_003
 * Dashboard summary/KPI service placeholder.
 * Controllers can gradually move dashboard calculations here.
 */
class SettlementSwDashboardService extends SettlementSwBaseService
{
    public function buildSummary(array $data = []): array
    {
        return [
            'total_meter_sales' => $this->money($data['total_meter_sales'] ?? 0),
            'total_payments' => $this->money($data['total_payments'] ?? 0),
            'total_expenses' => $this->money($data['total_expenses'] ?? 0),
            'balance' => $this->money(($data['total_meter_sales'] ?? 0) - ($data['total_payments'] ?? 0) - ($data['total_expenses'] ?? 0)),
        ];
    }
}
