<?php

namespace Modules\DistributionNew\Services\Sales;

use Modules\DistributionNew\Models\DisnewSalesCommission;

class DisnewCommissionService
{
    public function createCommission(array $data): DisnewSalesCommission
    {
        $base = (float)($data['base_amount'] ?? 0);
        $value = (float)($data['commission_value'] ?? 0);
        $type = $data['commission_type'] ?? 'percentage';
        $data['commission_amount'] = $type === 'percentage' ? round($base * ($value / 100), 4) : round($value, 4);
        return DisnewSalesCommission::create($data);
    }
}
