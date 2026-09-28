<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

class PumpOperatorLedgerPostingService
{
    public function post(PdSettlementPostingPayload $payload, SettlementPostingResult $result): void
    {
        $paymentTotals = $payload->totals['payment_totals'] ?? [];

        $result->posted('pump_operator_ledgers', [
            'pump_operator_id' => $payload->pumpOperatorId,
            'shortage' => (float) ($paymentTotals['shortage'] ?? 0),
            'excess' => (float) ($paymentTotals['excess'] ?? 0),
            'settlement_total' => (float) ($payload->totals['settlement_total'] ?? 0),
        ]);
    }
}
