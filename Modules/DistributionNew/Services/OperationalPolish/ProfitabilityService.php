<?php

namespace Modules\DistributionNew\Services\OperationalPolish;

use Modules\DistributionNew\Entities\OperationalPolish\ProfitabilityRun;
use Modules\DistributionNew\Entities\OperationalPolish\ProfitabilityLine;

class ProfitabilityService
{
    public function buildRun(array $data): ProfitabilityRun
    {
        $run = ProfitabilityRun::create([
            'business_id' => $data['business_id'],
            'location_id' => $data['location_id'] ?? null,
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'status' => 'calculated',
            'gross_sales' => $data['gross_sales'] ?? 0,
            'returns_amount' => $data['returns_amount'] ?? 0,
            'delivery_cost' => $data['delivery_cost'] ?? 0,
            'vehicle_cost' => $data['vehicle_cost'] ?? 0,
            'commission_cost' => $data['commission_cost'] ?? 0,
            'net_profit' => ($data['gross_sales'] ?? 0) - ($data['returns_amount'] ?? 0) - ($data['delivery_cost'] ?? 0) - ($data['vehicle_cost'] ?? 0) - ($data['commission_cost'] ?? 0),
            'created_by' => $data['created_by'] ?? null,
        ]);

        foreach (($data['lines'] ?? []) as $line) {
            ProfitabilityLine::create(array_merge($line, [
                'run_id' => $run->id,
                'business_id' => $run->business_id,
            ]));
        }

        return $run;
    }
}
