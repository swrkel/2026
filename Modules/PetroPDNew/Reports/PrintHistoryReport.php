<?php

namespace Modules\PetroPDNew\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PrintHistoryReport extends AbstractPdnewReport
{
    public function key(): string { return 'print_history'; }
    public function title(): string { return 'Print History'; }
    public function permission(): string { return 'petro_pd_new.reports.print_history'; }
    public function columns(): array { return [
            'printed_at' => 'Printed At',
            'document_type' => 'Document',
            'document_id' => 'Document ID',
            'print_type' => 'Print Type',
            'printed_by' => 'Printed By',
            'ip_address' => 'IP Address',
        ]; }

    public function query(int $businessId, array $filters = []): Builder
    {
        $query = DB::table('pdnew_print_logs')
            ->where('business_id', $businessId)
            ->select(
                'printed_at',
                'document_type',
                'document_id',
                'print_type',
                'printed_by',
                'ip_address'
            );

        $locationId = (int) ($filters['location_id'] ?? 0);
        if ($locationId > 0) {
            $query->where(function ($scope) use ($locationId): void {
                $scope->where(function ($settlementPrint) use ($locationId): void {
                    $settlementPrint->where('pdnew_print_logs.document_type', 'settlement')
                        ->whereExists(function ($source) use ($locationId): void {
                            $source->selectRaw('1')
                                ->from('pdnew_settlements as scoped_settlement')
                                ->whereColumn(
                                    'scoped_settlement.id',
                                    'pdnew_print_logs.document_id'
                                )
                                ->where('scoped_settlement.location_id', $locationId);
                        });
                })->orWhere(function ($dayEndPrint) use ($locationId): void {
                    $dayEndPrint->where('pdnew_print_logs.document_type', 'day_end')
                        ->whereExists(function ($source) use ($locationId): void {
                            $source->selectRaw('1')
                                ->from('pdnew_day_ends as scoped_day_end')
                                ->whereColumn(
                                    'scoped_day_end.id',
                                    'pdnew_print_logs.document_id'
                                )
                                ->where('scoped_day_end.location_id', $locationId);
                        });
                });
            });
        }

        $this->applyDate($query, 'printed_at', $filters);

        return $query->orderByDesc('printed_at')->orderByDesc('id');
    }
}
