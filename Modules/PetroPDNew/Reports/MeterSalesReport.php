<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MeterSalesReport extends AbstractPdnewReport
{
    public function key(): string { return 'meters'; }
    public function title(): string { return 'Meter Sales'; }
    public function permission(): string { return 'petro_pd_new.reports.meters'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'pump_id' => 'Pump',
            'product_id' => 'Product',
            'opening_meter' => 'Opening',
            'closing_meter' => 'Closing',
            'testing_quantity' => 'Testing',
            'sold_quantity' => 'Sold Qty',
            'unit_price' => 'Unit Price',
            'amount' => 'Amount',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_meter_sales as m')->join('pdnew_settlements as s','s.id','=','m.settlement_id')
            ->where('m.business_id',$businessId)->select('s.settlement_number','m.pump_id','m.product_id','m.opening_meter','m.closing_meter',
                'm.testing_quantity','m.sold_quantity','m.unit_price','m.amount');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'s.settlement_date',$filters);
        return $query->orderByDesc('s.settlement_date')->orderBy('m.pump_id');
    }
}
