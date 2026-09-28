<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ExcessCommissionsReport extends AbstractPdnewReport
{
    public function key(): string { return 'commissions'; }
    public function title(): string { return 'Excess Commissions'; }
    public function permission(): string { return 'petro_pd_new.reports.commissions'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'operator_name' => 'Operator',
            'commission_number' => 'Commission No',
            'commission_date' => 'Date',
            'base_excess_amount' => 'Base Excess',
            'commission_rate' => 'Rate',
            'commission_amount' => 'Commission',
            'status' => 'Status',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_commissions as c')->join('pdnew_settlements as s','s.id','=','c.settlement_id')
            ->where('c.business_id',$businessId)->select('s.settlement_number','s.operator_name','c.commission_number','c.commission_date','c.base_excess_amount','c.commission_rate','c.commission_amount','c.status');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'c.commission_date',$filters);
        return $query->orderByDesc('c.commission_date')->orderByDesc('c.id');
    }
}
