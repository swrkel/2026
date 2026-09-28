<?php
namespace Modules\Purchase\Utils;

class PurchaseTaxUtil
{
    public function calculate(float $amount, float $rate): float
    {
        return round(($amount * $rate) / 100, 4);
    }
}
