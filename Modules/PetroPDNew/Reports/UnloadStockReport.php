<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UnloadStockReport extends AbstractPdnewReport
{
    public function key(): string { return 'unloads'; }
    public function title(): string { return 'Unload Stock'; }
    public function permission(): string { return 'petro_pd_new.reports.unloads'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'receipt_number' => 'Receipt No',
            'bill_number' => 'Bill No',
            'unloaded_at' => 'Date/Time',
            'total_quantity' => 'Quantity',
            'total_amount' => 'Amount',
            'status' => 'Status',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_unload_stocks as u')->join('pdnew_settlements as s','s.id','=','u.settlement_id')
            ->where('u.business_id',$businessId)->select('s.settlement_number','u.receipt_number','u.bill_number','u.unloaded_at','u.total_quantity','u.total_amount','u.status');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'u.unloaded_at',$filters);
        return $query->orderByDesc('u.unloaded_at')->orderByDesc('u.id');
    }
}
