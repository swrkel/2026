<?php

namespace Modules\BankingMicrofinance\Services;

use Modules\BankingMicrofinance\Entities\FieldCashHandover;

class CashHandoverService
{
    public function calculateVariance(float $declaredAmount, float $receivedAmount): float
    {
        return round($receivedAmount - $declaredAmount, 4);
    }

    public function approve(FieldCashHandover $handover, int $userId): FieldCashHandover
    {
        $handover->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        return $handover->refresh();
    }
}
