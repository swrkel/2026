<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneShift;

final class ShiftSummaryReport extends AbstractPoneReport
{
    public function key(): string { return 'shifts'; }
    public function label(): string { return 'Shift Summary'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        return $this->applyScope(
            PoneShift::query()->with('operatorProfile'),
            $businessId,
            $filters,
            'opened_at'
        )->latest('opened_at')->limit($this->limit($filters))->get();
    }
}
