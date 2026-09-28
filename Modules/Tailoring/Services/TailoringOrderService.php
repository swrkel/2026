<?php

namespace Modules\Tailoring\Services;

use Illuminate\Support\Str;

class TailoringOrderService
{
    public function nextOrderNumber(): string
    {
        return 'TLR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    public function calculateBalance(float $total, float $advance): float
    {
        return max($total - $advance, 0);
    }
}
