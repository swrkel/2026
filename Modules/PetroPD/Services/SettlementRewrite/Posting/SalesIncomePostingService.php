<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

class SalesIncomePostingService
{
    public function post(PdSettlementPostingPayload $payload, SettlementPostingResult $result): void
    {
        $result->posted('sales_income_accounts', [
            'meter_sale_total' => (float) ($payload->totals['meter_sale_total'] ?? 0),
            'other_sale_total' => (float) ($payload->totals['other_sale_total'] ?? 0),
            'credit_sale_total' => (float) (($payload->totals['payment_totals'] ?? [])['credit_sale'] ?? 0),
        ]);
    }
}
