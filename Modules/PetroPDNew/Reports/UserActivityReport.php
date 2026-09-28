<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UserActivityReport extends AbstractPdnewReport
{
    public function key(): string { return 'activity'; }
    public function title(): string { return 'User Activity'; }
    public function permission(): string { return 'petro_pd_new.reports.activity'; }
    public function columns(): array { return [
            'created_at' => 'Date/Time',
            'user_id' => 'User ID',
            'action' => 'Action',
            'entity_type' => 'Entity',
            'entity_id' => 'Entity ID',
            'ip_address' => 'IP Address',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_audit_logs')->where('business_id',$businessId)
            ->select('created_at','user_id','action','entity_type','entity_id','ip_address');
        $this->applyLocation($query,'location_id',$filters); $this->applyDate($query,'created_at',$filters);
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
