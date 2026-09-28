<?php

namespace Modules\RiceMill\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\RiceMill\Models\PaddyReceipt;
use Modules\RiceMill\Reports\Concerns\AppliesOperationalScope;
use Modules\RiceMill\Reports\Concerns\ResolvesDateRange;

class PaddyReceivingReport
{
    use ResolvesDateRange, AppliesOperationalScope;

    public function data(int $businessId, Request $request): array
    {
        [$fromDate, $toDate] = $this->dates($request);
        $columns = [
            'receipt_no' => 'Receipt No',
            'received_at' => 'Received',
            'supplier_id' => 'Supplier ID',
            'vehicle_no' => 'Vehicle',
            'net_weight' => 'Net Weight',
            'moisture_percent' => 'Moisture %',
            'quality_grade' => 'Grade',
        ];
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $query = PaddyReceipt::forBusiness($businessId)
            ->whereBetween('received_at', [$from, $to]);
        $this->applyOperationalScope($query, $request);

        return [
            'title' => 'Paddy Receiving Report',
            'rows' => $query->select(array_keys($columns))->orderByDesc('received_at')->toBase()->get(),
            'columns' => $columns,
            'from' => $fromDate,
            'to' => $toDate,
        ];
    }
}
