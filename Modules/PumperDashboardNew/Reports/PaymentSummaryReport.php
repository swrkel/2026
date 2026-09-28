<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;
use Modules\PumperDashboardNew\Entities\PonePayment;

final class PaymentSummaryReport extends AbstractPoneReport
{
    public function key(): string { return 'payments'; }
    public function label(): string { return 'Payment Summary'; }

    public function rows(int $businessId, array $filters = []): Collection
    {
        $query = PonePayment::query()->with(['shift', 'creditSale.lines']);
        if (! empty($filters['payment_type'])) {
            $query->where('payment_type', (string) $filters['payment_type']);
        }

        return $this->applyScope($query, $businessId, $filters, 'transaction_at')
            ->latest('transaction_at')->limit($this->limit($filters))->get();
    }
}
