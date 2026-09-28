<?php

namespace Modules\POS\Services;

use Illuminate\Support\Facades\DB;

class POSDashboardService
{
    public function summary(int $businessId, $locationId = null, $dateRange = null): array
    {
        // Safe defaults: pages must load even before POS transactions exist.
        return [
            'sales_today' => $this->formatAmount(0),
            'payments_today' => $this->formatAmount(0),
            'open_registers' => 0,
            'held_sales' => 0,
            'cash_drawer' => $this->formatAmount(0),
            'refunds_today' => $this->formatAmount(0),
            'recent_sales' => [],
            'payment_mix' => [],
            'alerts' => [
                __('pos::messages.dashboard_ready'),
                __('pos::messages.configuration_ready'),
            ],
        ];
    }

    public function moduleStatus(int $businessId): array
    {
        return [
            ['name' => __('pos::messages.sidebar'), 'status' => __('pos::messages.active')],
            ['name' => __('pos::messages.dashboard'), 'status' => __('pos::messages.active')],
            ['name' => __('pos::messages.configuration_center'), 'status' => __('pos::messages.active')],
            ['name' => __('pos::messages.plugin_manager'), 'status' => __('pos::messages.ready')],
            ['name' => __('pos::messages.capability_manager'), 'status' => __('pos::messages.ready')],
            ['name' => __('pos::messages.multiple_payments'), 'status' => __('pos::messages.ready')],
        ];
    }

    protected function formatAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }
}
