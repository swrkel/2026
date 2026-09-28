<?php

namespace Modules\RiceMill\Reports;

use Illuminate\Http\Request;
use Modules\RiceMill\Models\Dispatch;
use Modules\RiceMill\Reports\Concerns\AppliesOperationalScope;
use Modules\RiceMill\Reports\Concerns\ResolvesDateRange;

class DispatchReport
{
    use ResolvesDateRange, AppliesOperationalScope;

    public function data(int $businessId, Request $request): array
    {
        [$from, $to] = $this->dates($request);
        $columns = [
            'dispatch_no' => 'Dispatch No',
            'dispatch_date' => 'Date',
            'customer_id' => 'Customer ID',
            'status' => 'Status',
            'net_total' => 'Net Total',
        ];

        $query = Dispatch::forBusiness($businessId)->whereBetween('dispatch_date', [$from, $to]);
        $this->applyOperationalScope($query, $request);

        return [
            'title' => 'Sales / Dispatch Report',
            'rows' => $query->select(array_keys($columns))->orderByDesc('dispatch_date')->toBase()->get(),
            'columns' => $columns,
            'from' => $from,
            'to' => $to,
        ];
    }
}
