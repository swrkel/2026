<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperatorPaymentsReport extends AbstractPdnewReport
{
    public function key(): string { return 'operator_payments'; }
    public function title(): string { return 'PONE Operator Payments'; }
    public function permission(): string { return 'petro_pd_new.reports.payments'; }
    public function columns(): array { return [
            'operator_name' => 'Operator',
            'payment_type' => 'Payment Type',
            'payment_count' => 'Count',
            'amount' => 'Amount',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_payments as p')->join('pdnew_settlements as s','s.id','=','p.settlement_id')
            ->where('p.business_id', $businessId)->where('p.status','active')
            ->select('s.operator_name','p.payment_type',DB::raw('COUNT(*) as payment_count'),DB::raw('SUM(p.amount) as amount'));
        $this->applyLocation($query, 's.location_id', $filters);
        $this->applyDate($query, 'p.transaction_at', $filters);
        return $query->groupBy('s.operator_name','p.payment_type')->orderBy('s.operator_name');
    }
}
