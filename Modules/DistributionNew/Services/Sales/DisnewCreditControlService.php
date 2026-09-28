<?php

namespace Modules\DistributionNew\Services\Sales;

use Modules\DistributionNew\Models\DisnewCreditControl;

class DisnewCreditControlService
{
    public function getRule(int $businessId, int $customerId): ?DisnewCreditControl
    {
        return DisnewCreditControl::where('business_id', $businessId)->where('customer_id', $customerId)->first();
    }

    public function validateOrderCredit(int $businessId, int $customerId, float $orderAmount, float $currentOutstanding = 0): array
    {
        $rule = $this->getRule($businessId, $customerId);
        if (!$rule || $rule->status !== 'active') {
            return ['allowed' => true, 'message' => null, 'warning' => null];
        }
        $limit = (float)$rule->credit_limit + (float)$rule->temporary_limit;
        $afterOrder = $currentOutstanding + $orderAmount;
        if ($rule->block_on_over_limit && $limit > 0 && $afterOrder > $limit) {
            return ['allowed' => false, 'message' => 'Customer credit limit exceeded.', 'warning' => null];
        }
        $warningLimit = $limit * ((float)$rule->warning_percentage / 100);
        return ['allowed' => true, 'message' => null, 'warning' => ($limit > 0 && $afterOrder >= $warningLimit) ? 'Customer is near credit limit.' : null];
    }
}
