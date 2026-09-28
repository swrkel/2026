<?php

namespace Modules\BeautySaloons\Services;

class SaleBillingService
{
    public function totals(array $lines, float $discount = 0, float $tax = 0): array
    {
        $subtotal = array_sum(array_map(fn($line) => (float)($line['qty'] ?? 1) * (float)($line['price'] ?? 0), $lines));
        $net = max(0, $subtotal - $discount + $tax);
        return compact('subtotal', 'discount', 'tax', 'net');
    }
}
