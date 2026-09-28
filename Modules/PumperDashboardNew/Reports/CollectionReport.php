<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneDailyCollection;

final class CollectionReport extends AbstractPoneReport
{
    public function key(): string { return 'collections'; }
    public function label(): string { return 'Daily Collections'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PoneDailyCollection::query()->with(['shift']);
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        return $this->applyScope($query, $businessId, $filters, 'collection_at')
            ->latest('collection_at')->limit($this->limit($filters))->get();
    }
}
