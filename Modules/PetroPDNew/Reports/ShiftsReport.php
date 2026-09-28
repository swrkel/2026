<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ShiftsReport extends AbstractPdnewReport
{
    public function key(): string { return 'shifts'; }
    public function title(): string { return 'Pumper Dashboard-New Closed Shifts'; }
    public function permission(): string { return 'petro_pd_new.reports.shifts'; }
    public function columns(): array { return [
            'pone_shift_number' => 'Shift No',
            'source_closed_at' => 'Closed At',
            'pone_operator_profile_id' => 'Operator Profile',
            'import_status' => 'Import Status',
            'settlement_id' => 'Settlement ID',
            'source_hash' => 'Source Hash',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_source_imports')->where('business_id', $businessId)
            ->select('pone_shift_number','source_closed_at','pone_operator_profile_id','import_status','settlement_id','source_hash');
        $this->applyLocation($query, 'location_id', $filters);
        $this->applyDate($query, 'source_closed_at', $filters);
        $this->applyStatus($query, 'import_status', $filters);
        return $query->orderByDesc('source_closed_at')->orderByDesc('id');
    }
}
