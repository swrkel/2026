<?php

namespace Modules\BeautySaloons\Services\Dashboard;

class BeautyAnalyticsService
{
    public function build(array $filters = []): array
    {
        $metricService = app(BeautyDashboardMetricService::class);
        return [
            'owner' => $metricService->owner($filters),
            'branch' => $metricService->branchManager($filters),
            'finance' => $metricService->finance($filters),
        ];
    }

    public function chartPayload(array $metrics): array
    {
        return [
            'revenue_trend' => $metrics['owner']['revenue_trend'] ?? [],
            'payment_mix' => $metrics['finance']['payment_mix'] ?? [],
            'top_services' => $metrics['owner']['top_services'] ?? [],
            'top_staff' => $metrics['owner']['top_staff'] ?? [],
        ];
    }
}
