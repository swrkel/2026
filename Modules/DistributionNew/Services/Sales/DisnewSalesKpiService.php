<?php

namespace Modules\DistributionNew\Services\Sales;

use Modules\DistributionNew\Models\DisnewSalesKpiSnapshot;

class DisnewSalesKpiService
{
    public function snapshot(array $data): DisnewSalesKpiSnapshot
    {
        $target = (float)($data['target_amount'] ?? 0);
        $net = (float)($data['net_sales'] ?? 0);
        $data['achievement_percentage'] = $target > 0 ? round(($net / $target) * 100, 3) : 0;
        return DisnewSalesKpiSnapshot::updateOrCreate([
            'business_id' => $data['business_id'],
            'snapshot_date' => $data['snapshot_date'],
            'sales_rep_id' => $data['sales_rep_id'] ?? null,
            'territory_id' => $data['territory_id'] ?? null,
            'route_id' => $data['route_id'] ?? null,
        ], $data);
    }
}
