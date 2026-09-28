<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneUnloadStock;

final class UnloadStockReport extends AbstractPoneReport
{
    public function key(): string { return 'unloads'; }
    public function label(): string { return 'Unload Stock'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PoneUnloadStock::query()->with(['shift', 'lines']);
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        return $this->applyScope($query, $businessId, $filters, 'unloaded_at')
            ->latest('unloaded_at')->limit($this->limit($filters))->get();
    }
}
