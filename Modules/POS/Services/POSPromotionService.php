<?php

namespace Modules\POS\Services;

class POSPromotionService
{
    public function applyBillDiscount(float $total, string $type, float $value): float
    {
        $discount = $type === 'percentage' ? ($total * $value / 100) : $value;
        return max(0, round($total - $discount, 4));
    }
}
