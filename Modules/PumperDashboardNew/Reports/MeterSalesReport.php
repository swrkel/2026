<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;

final class MeterSalesReport extends AbstractPoneReport
{
    public function key(): string { return 'meters'; }
    public function label(): string { return 'Meter Sales'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        return $this->applyScope(
            PonePumpAssignment::query()->with('shift')->where('status', 'closed'),
            $businessId,
            $filters,
            'closed_at'
        )->latest('closed_at')->limit($this->limit($filters))->get();
    }
}
