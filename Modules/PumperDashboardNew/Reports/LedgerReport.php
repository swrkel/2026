<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneOperatorLedgerEntry;

final class LedgerReport extends AbstractPoneReport
{
    public function key(): string { return 'ledger'; }
    public function label(): string { return 'Operator Ledger'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PoneOperatorLedgerEntry::query()->with(['shift', 'operatorProfile']);
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        return $this->applyScope($query, $businessId, $filters, 'entry_at')
            ->latest('entry_at')->limit($this->limit($filters))->get();
    }
}
