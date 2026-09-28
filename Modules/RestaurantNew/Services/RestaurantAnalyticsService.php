<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewAnalyticsSnapshot;
use Modules\RestaurantNew\Entities\RestaurantNewForecastRun;
use Modules\RestaurantNew\Entities\RestaurantNewHourlySalesTrend;
use Modules\RestaurantNew\Entities\RestaurantNewMenuProfitability;
use Modules\RestaurantNew\Entities\RestaurantNewTableUtilization;

class RestaurantAnalyticsService
{
    public function dashboard(int $businessId, ?int $locationId = null, ?string $from = null, ?string $to = null): array
    {
        $from = $from ?: now()->startOfMonth()->toDateString();
        $to = $to ?: now()->toDateString();

        $snapshots = RestaurantNewAnalyticsSnapshot::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->whereBetween('snapshot_date', [$from, $to]);

        $netSales = (clone $snapshots)->sum('net_sales');
        $foodCost = (clone $snapshots)->sum('food_cost_total');
        $grossProfit = (clone $snapshots)->sum('gross_profit');
        $orders = (clone $snapshots)->sum('order_count');

        return [
            'from' => $from,
            'to' => $to,
            'net_sales' => $netSales,
            'food_cost_total' => $foodCost,
            'gross_profit' => $grossProfit,
            'food_cost_percentage' => $netSales > 0 ? round(($foodCost / $netSales) * 100, 4) : 0,
            'order_count' => $orders,
            'average_order_value' => $orders > 0 ? round($netSales / $orders, 4) : 0,
            'top_items' => $this->topMenuItems($businessId, $locationId, $from, $to),
            'hourly_trends' => $this->hourlyTrends($businessId, $locationId, $from, $to),
            'table_utilization' => $this->tableUtilization($businessId, $locationId, $from, $to),
            'latest_forecast' => $this->latestForecast($businessId, $locationId),
        ];
    }

    public function topMenuItems(int $businessId, ?int $locationId, string $from, string $to)
    {
        return RestaurantNewMenuProfitability::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->whereBetween('period_date', [$from, $to])
            ->orderByDesc('gross_margin')
            ->limit(15)
            ->get();
    }

    public function hourlyTrends(int $businessId, ?int $locationId, string $from, string $to)
    {
        return RestaurantNewHourlySalesTrend::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->whereBetween('trend_date', [$from, $to])
            ->select('hour_no', DB::raw('SUM(net_sales) as net_sales'), DB::raw('SUM(order_count) as order_count'), DB::raw('SUM(guest_count) as guest_count'))
            ->groupBy('hour_no')
            ->orderBy('hour_no')
            ->get();
    }

    public function tableUtilization(int $businessId, ?int $locationId, string $from, string $to)
    {
        return RestaurantNewTableUtilization::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->whereBetween('utilization_date', [$from, $to])
            ->orderByDesc('sales_total')
            ->limit(20)
            ->get();
    }

    public function latestForecast(int $businessId, ?int $locationId = null)
    {
        return RestaurantNewForecastRun::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->latest('forecast_date')
            ->first();
    }

    public function generateSimpleForecast(int $businessId, ?int $locationId, string $forecastDate, ?int $createdBy = null): RestaurantNewForecastRun
    {
        $history = RestaurantNewAnalyticsSnapshot::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->where('snapshot_date', '<', $forecastDate)
            ->latest('snapshot_date')
            ->limit(28)
            ->get();

        $avgSales = $history->avg('net_sales') ?: 0;
        $avgOrders = (int) round($history->avg('order_count') ?: 0);

        return RestaurantNewForecastRun::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'forecast_date' => $forecastDate,
            'forecast_type' => 'daily_sales',
            'forecast_sales' => $avgSales,
            'forecast_orders' => $avgOrders,
            'confidence_score' => $history->count() >= 14 ? 0.75 : 0.45,
            'forecast_payload' => [
                'method' => 'rolling_28_day_average',
                'history_days_used' => $history->count(),
                'note' => 'Standalone RestaurantNew forecast foundation; can be enhanced later with seasonal rules.',
            ],
            'created_by' => $createdBy,
        ]);
    }
}
