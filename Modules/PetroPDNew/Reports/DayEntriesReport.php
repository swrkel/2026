<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DayEntriesReport extends AbstractPdnewReport
{
    public function key(): string { return 'day_entries'; }
    public function title(): string { return 'Day Entries'; }
    public function permission(): string { return 'petro_pd_new.reports.day_entries'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'entry_type' => 'Entry Type',
            'reference_no' => 'Reference',
            'entry_at' => 'Date/Time',
            'quantity' => 'Quantity',
            'amount' => 'Amount',
            'status' => 'Status',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_day_entries as d')->join('pdnew_settlements as s','s.id','=','d.settlement_id')
            ->where('d.business_id',$businessId)->select('s.settlement_number','d.entry_type','d.reference_no','d.entry_at','d.quantity','d.amount','d.status');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'d.entry_at',$filters);
        return $query->orderByDesc('d.entry_at')->orderByDesc('d.id');
    }
}
