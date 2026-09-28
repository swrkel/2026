<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PoneOtherSale;

final class OtherSalesReport extends AbstractPoneReport
{
    public function key(): string { return 'other-sales'; }
    public function label(): string { return 'Other Sales'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        return $this->applyScope(
            PoneOtherSale::query()->with(['shift', 'lines']),
            $businessId,
            $filters,
            'sale_at'
        )->latest('sale_at')->limit($this->limit($filters))->get();
    }
}
