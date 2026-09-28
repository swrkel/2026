<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class IntegrationReport extends AbstractPdnewReport
{
    public function key(): string { return 'integration'; }
    public function title(): string { return 'PD Integration'; }
    public function permission(): string { return 'petro_pd_new.reports.integration'; }
    public function columns(): array { return [
            'created_at' => 'Created At',
            'direction' => 'Direction',
            'operation' => 'Operation',
            'aggregate_type' => 'Aggregate',
            'aggregate_id' => 'ID',
            'status' => 'Status',
            'error_message' => 'Error',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_integration_logs')
            ->where('business_id', $businessId)
            ->select(
                'created_at',
                'direction',
                'operation',
                'aggregate_type',
                'aggregate_id',
                'status',
                'error_message'
            );

        $locationId = (int) ($filters['location_id'] ?? 0);
        if ($locationId > 0) {
            $this->scopeToLocation($query, $locationId);
        }

        $this->applyDate($query, 'created_at', $filters);
        $this->applyStatus($query, 'status', $filters);

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function scopeToLocation(Builder $query, int $locationId): void
    {
        $table = 'pdnew_integration_logs';
        $query->where(function ($scope) use ($locationId, $table): void {
            $scope->where(function ($settlementLog) use ($locationId, $table): void {
                $settlementLog->where($table . '.aggregate_type', 'settlement')
                    ->whereExists(function ($source) use ($locationId, $table): void {
                        $source->selectRaw('1')
                            ->from('pdnew_settlements as scoped_settlement')
                            ->whereColumn('scoped_settlement.id', $table . '.aggregate_id')
                            ->where('scoped_settlement.location_id', $locationId);
                    });
            })->orWhere(function ($dayEndLog) use ($locationId, $table): void {
                $dayEndLog->where($table . '.aggregate_type', 'day_end')
                    ->whereExists(function ($source) use ($locationId, $table): void {
                        $source->selectRaw('1')
                            ->from('pdnew_day_ends as scoped_day_end')
                            ->whereColumn('scoped_day_end.id', $table . '.aggregate_id')
                            ->where('scoped_day_end.location_id', $locationId);
                    });
            });
        });
    }
}
