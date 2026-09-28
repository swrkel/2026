<?php

namespace Modules\Purchase\Services\Dashboard;

use Modules\Purchase\Utils\Dashboard\PurchaseDashboardQueryUtil;

class PurchaseDashboardService
{
    public function summary(array $filters = []): array
    {
        return app(PurchaseDashboardQueryUtil::class)->summary($filters);
    }

    public function outstanding(array $filters = []): array
    {
        return app(PurchaseDashboardQueryUtil::class)->outstanding($filters);
    }

    public function recentPurchases(array $filters = []): array
    {
        return app(PurchaseDashboardQueryUtil::class)->recentPurchases($filters);
    }

    public function filters(): array
    {
        return [
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ];
    }
}
