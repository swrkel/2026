<?php

namespace Modules\DistributionNew\Services\Sales;

use Modules\DistributionNew\Models\DisnewDiscountRule;

class DisnewDiscountEngineService
{
    public function calculateDiscount(int $businessId, float $orderAmount, float $qty = 0): array
    {
        $rule = DisnewDiscountRule::where('business_id', $businessId)
            ->where('status', 'active')
            ->where(function ($q) { $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()->toDateString()); })
            ->where(function ($q) { $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()->toDateString()); })
            ->where('minimum_order_amount', '<=', $orderAmount)
            ->where('minimum_qty', '<=', $qty)
            ->orderByDesc('priority')
            ->first();
        if (!$rule) { return ['discount_amount' => 0, 'rule_id' => null, 'requires_approval' => false]; }
        $amount = $rule->discount_type === 'percentage' ? ($orderAmount * ((float)$rule->discount_value / 100)) : (float)$rule->discount_value;
        return ['discount_amount' => round($amount, 4), 'rule_id' => $rule->id, 'requires_approval' => (bool)$rule->requires_approval];
    }
}
