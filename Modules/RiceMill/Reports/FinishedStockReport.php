<?php

namespace Modules\RiceMill\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\RiceProduct;
use Modules\RiceMill\Reports\Concerns\AppliesOperationalScope;
use Modules\RiceMill\Reports\Concerns\ResolvesDateRange;

class FinishedStockReport
{
    use AppliesOperationalScope, ResolvesDateRange;

    public function data(int $businessId, Request $request): array
    {
        $columns = [
            'code' => 'Code',
            'name' => 'Product',
            'rice_type' => 'Rice Type',
            'current_qty' => 'Current Qty',
        ];

        [$from, $to] = $this->dates($request);

        $movementQuery = DB::table('rcm_finished_stock_movements as m')
            ->where('m.business_id', $businessId)
            ->whereBetween('m.movement_date', [$from, $to]);
        $this->applyOperationalScope($movementQuery, $request, 'm');

        $productIds = $movementQuery->distinct()->pluck('m.product_id')->all();
        $rows = RiceProduct::forBusiness($businessId)
            ->when($productIds, fn($q) => $q->whereIn('id', $productIds))
            ->when(! $productIds, fn($q) => $q->whereRaw('1=0'))
            ->select(array_keys($columns))
            ->orderBy('name')
            ->toBase()
            ->get();

        return [
            'title' => 'Finished Rice Stock Report',
            'rows' => $rows,
            'columns' => $columns,
            'from' => $from,
            'to' => $to,
        ];
    }
}
