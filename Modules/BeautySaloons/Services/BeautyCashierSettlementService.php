<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyBillPayment;
use Modules\BeautySaloons\Entities\BeautyCashierSettlement;

class BeautyCashierSettlementService
{
    public function expectedTotal(?int $cashierId, string $date): float
    {
        return (float) BeautyBillPayment::whereDate('created_at', $date)->sum('amount');
    }

    public function finalize(array $data): BeautyCashierSettlement
    {
        $expected = $this->expectedTotal($data['cashier_id'] ?? null, $data['settlement_date'] ?? now()->toDateString());
        $actual = (float)($data['actual_total'] ?? 0);
        return BeautyCashierSettlement::create([
            'business_id' => $data['business_id'] ?? null,
            'business_location_id' => $data['business_location_id'] ?? null,
            'settlement_date' => $data['settlement_date'] ?? now()->toDateString(),
            'cashier_id' => $data['cashier_id'] ?? null,
            'system_total' => $expected,
            'actual_total' => $actual,
            'shortage_amount' => max($expected - $actual, 0),
            'excess_amount' => max($actual - $expected, 0),
            'status' => 'finalized',
            'finalized_by' => $data['finalized_by'] ?? null,
            'finalized_at' => now(),
        ]);
    }
}
