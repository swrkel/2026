<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SourceIntegrityReport extends AbstractPdnewReport
{
    public function key(): string { return 'integrity'; }
    public function title(): string { return 'PONE Source Integrity'; }
    public function permission(): string { return 'petro_pd_new.reports.integrity'; }
    public function columns(): array { return [
            'pone_shift_number' => 'Shift No',
            'import_status' => 'Import Status',
            'source_hash' => 'Imported Hash',
            'current_hash' => 'Current Hash',
            'verified_at' => 'Verified At',
            'last_error' => 'Issue',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_source_imports')->where('business_id',$businessId)
            ->select('pone_shift_number','import_status','source_hash','current_hash','verified_at','last_error');
        $this->applyLocation($query,'location_id',$filters); $this->applyDate($query,'source_closed_at',$filters);
        return $query->orderByDesc('verified_at')->orderByDesc('id');
    }
}
