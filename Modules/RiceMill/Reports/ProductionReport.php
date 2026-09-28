<?php

namespace Modules\RiceMill\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\RiceMill\Models\ProductionBatch;
use Modules\RiceMill\Reports\Concerns\AppliesOperationalScope;
use Modules\RiceMill\Reports\Concerns\ResolvesDateRange;

class ProductionReport
{
    use ResolvesDateRange, AppliesOperationalScope;

    public function data(int $businessId, Request $request): array
    {
        [$fromDate, $toDate] = $this->dates($request);
        $columns = [
            'batch_no' => 'Batch',
            'status' => 'Status',
            'input_qty' => 'Paddy Input',
            'rice_output_qty' => 'Rice Output',
            'process_loss_qty' => 'Loss',
            'rice_yield_percent' => 'Yield %',
            'production_cost' => 'Production Cost',
            'cost_per_kg' => 'Cost / Kg',
        ];
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = ProductionBatch::forBusiness($businessId)
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('completed_at', [$from, $to])
                    ->orWhere(function ($query) use ($from, $to) {
                        $query->whereNull('completed_at')->whereBetween('started_at', [$from, $to]);
                    })
                    ->orWhere(function ($query) use ($from, $to) {
                        $query->whereNull('completed_at')->whereNull('started_at')->whereBetween('created_at', [$from, $to]);
                    });
            });
        $this->applyOperationalScope($query, $request);

        return [
            'title' => 'Production / Milling Report',
            'rows' => $query->select(array_keys($columns))->orderByDesc('id')->toBase()->get(),
            'columns' => $columns,
            'from' => $fromDate,
            'to' => $toDate,
        ];
    }
}
