<?php

namespace Modules\RiceMill\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\RiceMill\Models\Dispatch;
use Modules\RiceMill\Models\ProductionBatch;
use Modules\RiceMill\Reports\Concerns\AppliesOperationalScope;
use Modules\RiceMill\Reports\Concerns\ResolvesDateRange;

class ProfitabilityReport
{
    use ResolvesDateRange, AppliesOperationalScope;

    public function data(int $businessId, Request $request): array
    {
        [$fromDate, $toDate] = $this->dates($request);
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $productionQuery = ProductionBatch::forBusiness($businessId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to]);
        $this->applyOperationalScope($productionQuery, $request);
        $production = $productionQuery
            ->selectRaw('SUM(production_cost) cost, SUM(rice_output_qty) qty')
            ->toBase()
            ->first();

        $salesQuery = Dispatch::forBusiness($businessId)
            ->where('status', 'approved')
            ->whereBetween('dispatch_date', [$fromDate, $toDate]);
        $this->applyOperationalScope($salesQuery, $request);
        $sales = (float) $salesQuery->sum('net_total');

        $productionCost = (float) ($production->cost ?? 0);

        return [
            'data' => [
                'production_cost' => $productionCost,
                'rice_produced' => (float) ($production->qty ?? 0),
                'sales' => $sales,
                'gross_margin' => $sales - $productionCost,
            ],
            'f' => $fromDate,
            't' => $toDate,
        ];
    }
}
