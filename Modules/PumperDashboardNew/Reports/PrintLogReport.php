<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PonePrintLog;

final class PrintLogReport extends AbstractPoneReport
{
    public function key(): string { return 'print-logs'; }
    public function label(): string { return 'Print History'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PonePrintLog::query()->with(['shift'])->where('business_id', $businessId);
        if (! empty($filters['operator_profile_id'])) {
            $query->where('operator_profile_id', (int) $filters['operator_profile_id']);
        }
        if (! empty($filters['from'])) $query->whereDate('printed_at', '>=', $filters['from']);
        if (! empty($filters['to'])) $query->whereDate('printed_at', '<=', $filters['to']);
        if (! empty($filters['printable_type'])) $query->where('printable_type', (string) $filters['printable_type']);
        return $query->latest('printed_at')->limit($this->limit($filters))->get();
    }
}
