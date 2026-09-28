<?php

namespace Modules\ExpensesNew\Services\Analytics;

class VendorSpendAnalyticsService
{
    public function summary(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function trend(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function categoryBreakdown(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }
}
