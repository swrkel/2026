<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperatorSummaryReport extends AbstractPdnewReport
{
    public function key(): string { return 'operators'; }
    public function title(): string { return 'Operator Summary'; }
    public function permission(): string { return 'petro_pd_new.reports.operators'; }
    public function columns(): array { return [
            'operator_name' => 'Operator',
            'settlement_count' => 'Settlements',
            'meter_sales_total' => 'Meter Sales',
            'received_total' => 'Normal Collections',
            'classified_shortage_total' => 'Classified Shortage',
            'classified_excess_total' => 'Classified Excess',
            'variance_amount' => 'Unresolved Variance',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlements')->where('business_id', $businessId)
            ->select(
                'operator_name',
                DB::raw('COUNT(*) as settlement_count'),
                DB::raw('SUM(meter_sales_total) as meter_sales_total'),
                DB::raw('SUM(received_total) as received_total'),
                DB::raw('SUM(source_shortage_total + manual_shortage_total) as classified_shortage_total'),
                DB::raw('SUM(source_excess_total + manual_excess_total) as classified_excess_total'),
                DB::raw('SUM(variance_amount) as variance_amount')
            );
        $this->applyLocation($query, 'location_id', $filters);
        $this->applyDate($query, 'settlement_date', $filters);
        return $query->groupBy('operator_name')->orderBy('operator_name');
    }
}
