<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SettlementsReport extends AbstractPdnewReport
{
    public function key(): string { return 'settlements'; }
    public function title(): string { return 'PD Settlements'; }
    public function permission(): string { return 'petro_pd_new.reports.settlements'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'settlement_date' => 'Date',
            'operator_name' => 'Operator',
            'status' => 'Status',
            'expected_total' => 'Expected',
            'received_total' => 'Normal Collections',
            'classified_shortage_total' => 'Classified Shortage',
            'classified_excess_total' => 'Classified Excess',
            'operational_variance_amount' => 'Operational Difference',
            'variance_amount' => 'Unresolved Variance',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlements')->where('business_id', $businessId)
            ->select(
                'settlement_number', 'settlement_date', 'operator_name', 'status',
                'expected_total', 'received_total', 'operational_variance_amount',
                'variance_amount',
                DB::raw('(source_shortage_total + manual_shortage_total) as classified_shortage_total'),
                DB::raw('(source_excess_total + manual_excess_total) as classified_excess_total')
            );
        $this->applyLocation($query, 'location_id', $filters);
        $this->applyDate($query, 'settlement_date', $filters);
        $this->applyStatus($query, 'status', $filters);
        return $query->orderByDesc('settlement_date')->orderByDesc('id');
    }
}
