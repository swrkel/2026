<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SettlementPaymentsReport extends AbstractPdnewReport
{
    public function key(): string { return 'payments'; }
    public function title(): string { return 'Settlement Payments'; }
    public function permission(): string { return 'petro_pd_new.reports.payments'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'payment_number' => 'Payment No',
            'payment_type' => 'Type',
            'reference_no' => 'Reference',
            'transaction_at' => 'Date/Time',
            'amount' => 'Amount',
            'source_label' => 'Source',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_payments as p')->join('pdnew_settlements as s','s.id','=','p.settlement_id')
            ->where('p.business_id',$businessId)->select('s.settlement_number','p.payment_number','p.payment_type','p.reference_no',
                'p.transaction_at','p.amount',DB::raw("CASE WHEN p.is_source=1 THEN 'Pumper Dashboard-New' ELSE 'Petro PD-New' END as source_label"));
        $this->applyLocation($query, 's.location_id', $filters);
        $this->applyDate($query, 'p.transaction_at', $filters);
        $this->applyStatus($query, 'p.status', $filters);
        return $query->orderByDesc('p.transaction_at')->orderByDesc('p.id');
    }
}
