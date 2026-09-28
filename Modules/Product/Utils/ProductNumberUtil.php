<?php

namespace Modules\Product\Utils;

use App\Business;

class ProductNumberUtil
{
    public function money($value, ?int $businessId = null): string
    {
        return number_format((float) $value, $this->currencyPrecision($businessId), '.', ',');
    }

    public function qty($value, ?int $businessId = null, bool $isFuel = false): string
    {
        return number_format((float) $value, $isFuel ? 3 : $this->quantityPrecision($businessId), '.', ',');
    }

    public function currencyPrecision(?int $businessId = null): int
    {
        $businessId = $businessId ?: (int) session('user.business_id');
        return (int) (Business::where('id', $businessId)->value('currency_precision') ?? 2);
    }

    public function quantityPrecision(?int $businessId = null): int
    {
        $businessId = $businessId ?: (int) session('user.business_id');
        return (int) (Business::where('id', $businessId)->value('quantity_precision') ?? 2);
    }
}
