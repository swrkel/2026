<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Support\Facades\DB;

class AdjustmentNumberService
{
    public function __construct(private StockAdjustmentSettingsService $settings) {}

    public function next(?int $businessId): string
    {
        $values = $this->settings->values($businessId);
        $prefix = trim((string) ($values['number_prefix'] ?? 'SAN-'));
        $padding = max(3, min(10, (int) ($values['number_padding'] ?? 5)));

        $next = (int) DB::table('san_stock_adjustments')
            ->when($businessId, fn ($query) => $query->where('business_id', $businessId))
            ->lockForUpdate()
            ->count() + 1;

        return $prefix . now()->format('Ymd') . '-' . str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
    }
}
