<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneExcessCommission;

final class ExcessCommissionReport extends AbstractPoneReport
{
    public function key(): string { return 'commissions'; }
    public function label(): string { return 'Excess Commissions'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PoneExcessCommission::query()->with(['shift', 'operatorProfile']);
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        return $this->applyScope($query, $businessId, $filters, 'commission_date')
            ->latest('commission_date')->limit($this->limit($filters))->get();
    }
}
