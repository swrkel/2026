<?php

namespace Modules\RiceMill\Reports;

use Illuminate\Http\Request;
use Modules\RiceMill\Models\PaddyPurchase;
use Modules\RiceMill\Reports\Concerns\AppliesOperationalScope;
use Modules\RiceMill\Reports\Concerns\ResolvesDateRange;

class PaddyPurchaseReport
{
    use ResolvesDateRange, AppliesOperationalScope;

    public function data(int $businessId, Request $request): array
    {
        [$from, $to] = $this->dates($request);
        $columns = [
            'purchase_no' => 'Purchase No',
            'purchase_date' => 'Date',
            'supplier_id' => 'Supplier ID',
            'status' => 'Status',
            'net_total' => 'Net Total',
        ];

        $query = PaddyPurchase::forBusiness($businessId)
            ->whereBetween('purchase_date', [$from, $to]);
        $this->applyOperationalScope($query, $request);

        return [
            'title' => 'Paddy Purchase Report',
            'rows' => $query->select(array_keys($columns))->orderByDesc('purchase_date')->toBase()->get(),
            'columns' => $columns,
            'from' => $from,
            'to' => $to,
        ];
    }
}
