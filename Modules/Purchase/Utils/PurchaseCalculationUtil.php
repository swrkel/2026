<?php
namespace Modules\Purchase\Utils;

class PurchaseCalculationUtil
{
    public function lineTotal(float $qty, float $price): float
    {
        return round($qty * $price, 4);
    }
}
