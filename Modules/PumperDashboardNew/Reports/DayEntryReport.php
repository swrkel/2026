<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneDayEntry;

final class DayEntryReport extends AbstractPoneReport
{
    public function key(): string { return 'day-entries'; }
    public function label(): string { return 'Day Entries'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PoneDayEntry::query()->with(['shift', 'assignment']);
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        return $this->applyScope($query, $businessId, $filters, 'entry_at')
            ->latest('entry_at')->limit($this->limit($filters))->get();
    }
}
