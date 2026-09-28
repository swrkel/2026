<?php

namespace Modules\RestaurantNew\Services\Ai;

use Modules\RestaurantNew\Entities\RestaurantNewAiAnomalyLog;
use Modules\RestaurantNew\Entities\RestaurantNewAiForecast;
use Modules\RestaurantNew\Entities\RestaurantNewAiInventorySignal;
use Modules\RestaurantNew\Entities\RestaurantNewAiRecommendation;
use Modules\RestaurantNew\Entities\RestaurantNewAnalyticsSnapshot;
use Modules\RestaurantNew\Entities\RestaurantNewMenuProfitability;

class RestaurantAiOperationsService
{
    public function commandCenter(int $businessId, ?int $locationId = null): array
    {
        return [
            'open_recommendations' => RestaurantNewAiRecommendation::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->where('status', 'open')->latest()->limit(20)->get(),
            'critical_inventory' => RestaurantNewAiInventorySignal::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->whereIn('priority', ['high','critical'])->latest()->limit(20)->get(),
            'recent_anomalies' => RestaurantNewAiAnomalyLog::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->latest()->limit(20)->get(),
            'latest_forecast' => RestaurantNewAiForecast::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->latest('forecast_date')->first(),
        ];
    }

    public function generateDailySalesForecast(int $businessId, ?int $locationId, string $forecastDate, ?int $userId = null): RestaurantNewAiForecast
    {
        $history = RestaurantNewAnalyticsSnapshot::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->where('snapshot_date', '<', $forecastDate)
            ->latest('snapshot_date')->limit(35)->get();

        $predictedSales = round((float) ($history->avg('net_sales') ?: 0), 4);
        $predictedOrders = (int) round($history->avg('order_count') ?: 0);
        $predictedGuests = (int) round($history->avg('guest_count') ?: 0);

        return RestaurantNewAiForecast::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'forecast_date' => $forecastDate,
            'forecast_area' => 'daily_sales',
            'predicted_sales' => $predictedSales,
            'predicted_orders' => $predictedOrders,
            'predicted_guests' => $predictedGuests,
            'confidence_score' => $history->count() >= 21 ? 0.78 : 0.48,
            'method' => 'rolling_35_day_average',
            'forecast_payload' => ['history_days_used' => $history->count(), 'standalone_module' => 'RestaurantNew'],
            'created_by' => $userId,
        ]);
    }

    public function detectMenuProfitAlerts(int $businessId, ?int $locationId = null): int
    {
        $items = RestaurantNewMenuProfitability::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->where('margin_percentage', '<', 15)
            ->latest('period_date')->limit(25)->get();

        $count = 0;
        foreach ($items as $item) {
            RestaurantNewAiRecommendation::create([
                'business_id' => $businessId,
                'location_id' => $item->location_id,
                'recommendation_type' => 'menu_profitability',
                'priority' => 'high',
                'title' => 'Review low-margin menu item #' . $item->menu_item_id,
                'description' => 'This menu item has a low margin percentage. Check recipe cost, selling price, wastage and discounts.',
                'source_payload' => $item->toArray(),
                'action_payload' => ['recommended_action' => 'review_price_or_recipe_cost'],
            ]);
            $count++;
        }
        return $count;
    }

    public function logAnomaly(int $businessId, ?int $locationId, string $area, string $severity, string $title, ?string $details = null, array $metrics = []): RestaurantNewAiAnomalyLog
    {
        return RestaurantNewAiAnomalyLog::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'anomaly_area' => $area,
            'severity' => $severity,
            'title' => $title,
            'details' => $details,
            'metric_payload' => $metrics,
        ]);
    }
}
