<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperatorLedgerReport extends AbstractPdnewReport
{
    public function key(): string { return 'ledger'; }
    public function title(): string { return 'Operator Ledger'; }
    public function permission(): string { return 'petro_pd_new.reports.ledger'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'operator_name' => 'Operator',
            'entry_at' => 'Date/Time',
            'reference_no' => 'Reference',
            'description' => 'Description',
            'debit' => 'Debit',
            'credit' => 'Credit',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_ledger_entries as l')->join('pdnew_settlements as s','s.id','=','l.settlement_id')
            ->where('l.business_id',$businessId)->select('s.settlement_number','s.operator_name','l.entry_at','l.reference_no','l.description','l.debit','l.credit');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'l.entry_at',$filters);
        return $query->orderByDesc('l.entry_at')->orderByDesc('l.id');
    }
}
