<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneShortageRecovery;

final class ShortageRecoveryReport extends AbstractPoneReport
{
    public function key(): string { return 'shortages'; }
    public function label(): string { return 'Shortage Recoveries'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PoneShortageRecovery::query()->with(['shift', 'operatorProfile']);
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        return $this->applyScope($query, $businessId, $filters, 'recovery_date')
            ->latest('recovery_date')->limit($this->limit($filters))->get();
    }
}
