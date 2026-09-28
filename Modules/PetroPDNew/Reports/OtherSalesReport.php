<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OtherSalesReport extends AbstractPdnewReport
{
    public function key(): string { return 'other_sales'; }
    public function title(): string { return 'Other Sales'; }
    public function permission(): string { return 'petro_pd_new.reports.other_sales'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'sale_number' => 'Sale No',
            'sale_at' => 'Date/Time',
            'gross_amount' => 'Gross',
            'discount_amount' => 'Discount',
            'net_amount' => 'Net',
            'status' => 'Status',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_other_sales as o')->join('pdnew_settlements as s','s.id','=','o.settlement_id')
            ->where('o.business_id',$businessId)->select('s.settlement_number','o.sale_number','o.sale_at','o.gross_amount','o.discount_amount','o.net_amount','o.status');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'o.sale_at',$filters);
        return $query->orderByDesc('o.sale_at')->orderByDesc('o.id');
    }
}
