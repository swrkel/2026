<?php

namespace Modules\RiceMill\Reports;

use Illuminate\Http\Request;
use Modules\RiceMill\Models\PaddyLot;
use Modules\RiceMill\Reports\Concerns\AppliesOperationalScope;
use Modules\RiceMill\Reports\Concerns\ResolvesDateRange;

class PaddyStockReport
{
    use AppliesOperationalScope, ResolvesDateRange;

    public function data(int $businessId, Request $request): array
    {
        $columns = [
            'lot_no' => 'Lot',
            'received_date' => 'Received',
            'paddy_variety_id' => 'Variety ID',
            'original_qty' => 'Original Qty',
            'balance_qty' => 'Balance Qty',
            'quality_grade' => 'Grade',
        ];

        [$from, $to] = $this->dates($request);
        $query = PaddyLot::forBusiness($businessId)->where('balance_qty', '>', 0)->whereBetween('received_date', [$from, $to]);
        $this->applyOperationalScope($query, $request);

        return [
            'title' => 'Paddy Stock Report',
            'rows' => $query->select(array_keys($columns))->orderBy('received_date')->toBase()->get(),
            'columns' => $columns,
            'from' => $from,
            'to' => $to,
        ];
    }
}
