<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DayEndReport extends AbstractPdnewReport
{
    public function key(): string { return 'day_end'; }
    public function title(): string { return 'Day End'; }
    public function permission(): string { return 'petro_pd_new.reports.day_end'; }
    public function columns(): array { return [
            'day_end_number' => 'Day End No',
            'day_end_date' => 'Date',
            'status' => 'Status',
            'settlement_count' => 'Settlements',
            'settlements_total' => 'Settlement Total',
            'payments_total' => 'Payments',
            'variance_total' => 'Variance',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_day_ends')->where('business_id',$businessId)
            ->select('day_end_number','day_end_date','status','settlement_count','settlements_total','payments_total','variance_total');
        $this->applyLocation($query,'location_id',$filters); $this->applyDate($query,'day_end_date',$filters); $this->applyStatus($query,'status',$filters);
        return $query->orderByDesc('day_end_date')->orderByDesc('id');
    }
}
