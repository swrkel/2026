<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CollectionsReport extends AbstractPdnewReport
{
    public function key(): string { return 'collections'; }
    public function title(): string { return 'Daily Collections'; }
    public function permission(): string { return 'petro_pd_new.reports.collections'; }
    public function columns(): array { return [
            'settlement_number' => 'Settlement No',
            'collection_number' => 'Collection No',
            'collection_at' => 'Date/Time',
            'expected_amount' => 'Expected',
            'declared_amount' => 'Declared',
            'difference_amount' => 'Difference',
            'status' => 'Status',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_settlement_collections as c')->join('pdnew_settlements as s','s.id','=','c.settlement_id')
            ->where('c.business_id',$businessId)->select('s.settlement_number','c.collection_number','c.collection_at','c.expected_amount','c.declared_amount','c.difference_amount','c.status');
        $this->applyLocation($query,'s.location_id',$filters); $this->applyDate($query,'c.collection_at',$filters);
        return $query->orderByDesc('c.collection_at')->orderByDesc('c.id');
    }
}
