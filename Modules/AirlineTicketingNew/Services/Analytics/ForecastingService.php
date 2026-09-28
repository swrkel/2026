<?php
namespace Modules\AirlineTicketingNew\Services\Analytics;

use Modules\AirlineTicketingNew\Entities\AnalyticsSnapshot;

class ForecastingService
{
    public function movingAverage(int $businessId, string $metric, int $periods = 30, int $futureDays = 30): array
    {
        $rows = AnalyticsSnapshot::query()
            ->where('business_id', $businessId)
            ->latest('snapshot_date')
            ->limit($periods)
            ->get();

        $average = (float) $rows->avg(
            fn ($row) => (float) data_get($row->metrics_json, $metric, 0)
        );

        return collect(range(1, $futureDays))->map(fn ($day) => [
            'date' => now()->addDays($day)->toDateString(),
            'metric' => $metric,
            'forecast_value' => round($average, 4),
        ])->all();
    }
}
