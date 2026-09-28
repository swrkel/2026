<?php
namespace Modules\AirlineTicketingNew\Services\Analytics;

use Modules\AirlineTicketingNew\Entities\AnalyticsSnapshot;

class SimpleForecastService
{
    public function forecastSales(int $businessId, int $days = 7): array
    {
        $rows = AnalyticsSnapshot::query()
            ->where('business_id', $businessId)
            ->latest('snapshot_date')
            ->limit(30)
            ->get();

        $average = $rows->avg(fn ($row) => (float) data_get($row->metrics_json, 'sales', 0));

        return collect(range(1, $days))->map(fn ($offset) => [
            'date' => now()->addDays($offset)->toDateString(),
            'forecast_sales' => round((float) $average, 4),
        ])->all();
    }
}
