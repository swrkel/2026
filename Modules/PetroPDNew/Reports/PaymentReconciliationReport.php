<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PaymentReconciliationReport extends AbstractPdnewReport
{
    public function key(): string { return 'reconciliation'; }
    public function title(): string { return 'Payment Reconciliation'; }
    public function permission(): string { return 'petro_pd_new.reports.reconciliation'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'settlement_date' => 'Date',
            'operator_name' => 'Operator',
            'expected_total' => 'Expected',
            'source_declared_total' => 'PONE Declared',
            'received_total' => 'Normal Collections',
            'classified_shortage_total' => 'Classified Shortage',
            'classified_excess_total' => 'Classified Excess',
            'operational_variance_amount' => 'Operational Difference',
            'variance_amount' => 'Unresolved Variance',
            'reconciliation_status' => 'Status',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlements')->where('business_id', $businessId)
            ->select(
                'settlement_number', 'settlement_date', 'operator_name',
                'expected_total', 'source_declared_total', 'received_total',
                'operational_variance_amount', 'variance_amount',
                'reconciliation_status',
                DB::raw('(source_shortage_total + manual_shortage_total) as classified_shortage_total'),
                DB::raw('(source_excess_total + manual_excess_total) as classified_excess_total')
            );
        $this->applyLocation($query, 'location_id', $filters);
        $this->applyDate($query, 'settlement_date', $filters);
        if (($filters['only_variance'] ?? null) === '1') {
            $query->whereRaw('ABS(variance_amount) >= 0.00005');
        }
        return $query->orderByDesc('settlement_date')->orderByDesc('id');
    }
}
