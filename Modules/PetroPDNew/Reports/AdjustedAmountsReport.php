<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AdjustedAmountsReport extends AbstractPdnewReport
{
    public function key(): string { return 'adjustments'; }
    public function title(): string { return 'Adjusted Amounts'; }
    public function permission(): string { return 'petro_pd_new.reports.adjustments'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'adjustment_number' => 'Adjustment No',
            'field_name' => 'Field',
            'current_amount' => 'Before',
            'requested_amount' => 'Requested',
            'approved_amount' => 'Approved',
            'status' => 'Status',
            'requested_at' => 'Requested At',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_adjustments as a')->join('pdnew_settlements as s','s.id','=','a.settlement_id')
            ->where('a.business_id',$businessId)->select('s.settlement_number','a.adjustment_number','a.field_name','a.current_amount','a.requested_amount','a.approved_amount','a.status','a.requested_at');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'a.requested_at',$filters); $this->applyStatus($query,'a.status',$filters);
        return $query->orderByDesc('a.requested_at')->orderByDesc('a.id');
    }
}
