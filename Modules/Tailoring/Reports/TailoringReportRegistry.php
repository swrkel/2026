<?php

namespace Modules\Tailoring\Reports;

class TailoringReportRegistry
{
    public static function reports(): array
    {
        return [
            ['key' => 'pending_orders', 'name' => 'Pending Orders'],
            ['key' => 'delivery_schedule', 'name' => 'Delivery Schedule'],
            ['key' => 'tailor_productivity', 'name' => 'Tailor Productivity'],
            ['key' => 'fabric_consumption', 'name' => 'Fabric Consumption'],
            ['key' => 'rework_analysis', 'name' => 'Rework Analysis'],
            ['key' => 'branch_profitability', 'name' => 'Branch Profitability'],
        ];
    }
}
