<?php

namespace Modules\PriceChangeNew\Services;

use InvalidArgumentException;

class PriceCalculator
{
    /** @return array{ex_tax: float, inc_tax: float} */
    public function normalize(float $enteredPrice, string $basis, float $taxRate): array
    {
        if ($enteredPrice < 0) {
            throw new InvalidArgumentException('Price cannot be negative.');
        }

        $factor = 1 + ($taxRate / 100);
        if ($basis === 'inc_tax') {
            $inc = $enteredPrice;
            $ex = $factor > 0 ? $enteredPrice / $factor : $enteredPrice;
        } else {
            $ex = $enteredPrice;
            $inc = $enteredPrice * $factor;
        }

        return [
            'ex_tax' => round($ex, 8),
            'inc_tax' => round($inc, 8),
        ];
    }

    public function profitPercent(float $purchaseExTax, float $sellingExTax): ?float
    {
        if ($purchaseExTax <= 0) {
            return null;
        }

        return round((($sellingExTax - $purchaseExTax) / $purchaseExTax) * 100, 8);
    }
}
