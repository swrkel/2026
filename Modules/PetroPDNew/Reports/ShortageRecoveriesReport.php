<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ShortageRecoveriesReport extends AbstractPdnewReport
{
    public function key(): string { return 'shortages'; }
    public function title(): string { return 'Shortage Recoveries'; }
    public function permission(): string { return 'petro_pd_new.reports.shortages'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'operator_name' => 'Operator',
            'recovery_number' => 'Recovery No',
            'recovery_date' => 'Date',
            'payment_method' => 'Method',
            'amount' => 'Amount',
            'status' => 'Status',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_recoveries as r')->join('pdnew_settlements as s','s.id','=','r.settlement_id')
            ->where('r.business_id',$businessId)->select('s.settlement_number','s.operator_name','r.recovery_number','r.recovery_date','r.payment_method','r.amount','r.status');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'r.recovery_date',$filters);
        return $query->orderByDesc('r.recovery_date')->orderByDesc('r.id');
    }
}
