<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneAuditLog;

final class AuditTrailReport extends AbstractPoneReport
{
    public function key(): string { return 'audit'; }
    public function label(): string { return 'Audit Trail'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PoneAuditLog::query();
        if (! empty($filters['action'])) {
            $query->where('action', 'like', '%' . trim((string) $filters['action']) . '%');
        }

        return $this->applyScope($query, $businessId, $filters, 'created_at')
            ->latest('created_at')->limit($this->limit($filters))->get();
    }
}
